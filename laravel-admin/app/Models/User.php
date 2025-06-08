<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Log;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Indicates if the model should be timestamped.
     * Only created_at exists in the existing table.
     */
    public $timestamps = true;

    /**
     * The name of the "updated at" column.
     * Set to null since the existing table doesn't have updated_at.
     */
    const UPDATED_AT = null;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'username',
        'password',
        'siteid',
        'license',
        'role',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'siteid' => 'integer',
            'license' => 'integer',
            'created_at' => 'datetime',
            'role' => 'string',
        ];
    }

    /**
     * OLD PHP SYSTEM MAPPING - Username to User ID mapping from the old system
     * This matches the exact switch statement from admin/admin/index.php
     */
    public static function getOldPhpUserIdMapping($username)
    {
        Log::info('User::getOldPhpUserIdMapping called', ['username' => $username]);

        $mapping = [
            'steinkjer' => 17,
            'namsos' => 10,
            'lade' => 11,
            'moan' => 12,
            'gramyra' => 13,
            'frosta' => 14,
            'admin' => 0, // Admin user
        ];

        $userId = $mapping[$username] ?? 0;

        Log::info('User::getOldPhpUserIdMapping result', [
            'username' => $username,
            'mapped_user_id' => $userId,
            'is_admin' => $userId === 0
        ]);

        return $userId;
    }

    /**
     * OLD PHP SYSTEM MAPPING - User ID to Site ID mapping from welcome.php
     */
    public static function getUserIdToSiteIdMapping($userId)
    {
        Log::info('User::getUserIdToSiteIdMapping called', ['user_id' => $userId]);

        $mapping = [
            17 => 13, // steinkjer
            10 => 7,  // namsos
            11 => 4,  // lade
            12 => 6,  // moan
            13 => 5,  // gramyra
            14 => 10, // frosta
            16 => 11, // unknown location
            0 => 0,   // admin
        ];

        $siteId = $mapping[$userId] ?? 0;

        Log::info('User::getUserIdToSiteIdMapping result', [
            'user_id' => $userId,
            'mapped_site_id' => $siteId
        ]);

        return $siteId;
    }

    /**
     * Create or update user using old PHP system logic
     */
    public static function createFromOldPhpAuth($username)
    {
        Log::info('User::createFromOldPhpAuth called', ['username' => $username]);

        $oldPhpUserId = self::getOldPhpUserIdMapping($username);
        $siteId = self::getUserIdToSiteIdMapping($oldPhpUserId);

        $user = self::updateOrCreate(
            ['username' => $username],
            [
                'siteid' => $siteId,
                'license' => $oldPhpUserId === 0 ? 9999 : 100 + $oldPhpUserId, // Admin gets high license, others get based on user ID
                'password' => bcrypt('placeholder'), // Will be overridden by super password
            ]
        );

        Log::info('User::createFromOldPhpAuth result', [
            'username' => $username,
            'user_id' => $user->id,
            'siteid' => $user->siteid,
            'license' => $user->license,
            'is_admin' => $user->isAdmin()
        ]);

        return $user;
    }

    /**
     * Get the location associated with the user.
     */
    public function location()
    {
        return $this->belongsTo(Location::class, 'siteid', 'site_id');
    }

    /**
     * Get the avdeling associated with the user.
     */
    public function avdeling()
    {
        return $this->belongsTo(Avdeling::class, 'siteid', 'siteid');
    }

    /**
     * Get orders for this user's location.
     */
    public function orders()
    {
        return $this->hasMany(Order::class, 'site', 'siteid');
    }

    /**
     * Get opening hours managed by this user.
     */
    public function openingHours()
    {
        return $this->hasMany(OpeningHours::class, 'userid');
    }

    /**
     * Get the username field for authentication.
     */
    public function getAuthIdentifierName()
    {
        return 'username';
    }

    /**
     * Get the unique identifier for authentication (should return ID for session storage).
     */
    public function getAuthIdentifier()
    {
        return $this->id;  // Return numeric ID, not username
    }

    /**
     * Get the site associated with this user.
     */
    public function site()
    {
        return $this->belongsTo(Site::class, 'siteid', 'site_id');
    }

    /**
     * Get the alternative avdeling associated with this user.
     */
    public function avdelingAlternative()
    {
        return $this->belongsTo(AvdelingAlternative::class, 'siteid', 'SiteID');
    }

    /**
     * Check if user has valid license.
     */
    public function hasValidLicense()
    {
        return $this->license > 0;
    }

    /**
     * Get user by site ID.
     */
    public static function findBySiteId($siteId)
    {
        return static::where('siteid', $siteId)->first();
    }

    /**
     * Check if user is admin.
     * Based on the old PHP system: admin users have specific usernames or siteid = 0
     */
    public function isAdmin()
    {
        // Check role field first, then fall back to old logic
        if (isset($this->role)) {
            return $this->role === 'admin';
        }
        
        // Old logic for backwards compatibility
        $isAdmin = in_array($this->username, ['admin']) || $this->siteid === 0;

        Log::info('User::isAdmin check', [
            'username' => $this->username,
            'siteid' => $this->siteid,
            'role' => $this->role ?? 'not set',
            'is_admin' => $isAdmin
        ]);

        return $isAdmin;
    }

    /**
     * Check if user can access admin functions.
     */
    public function canAccessAdmin()
    {
        return $this->isAdmin();
    }

    /**
     * Get user role name.
     */
    public function getRoleName()
    {
        return $this->isAdmin() ? 'Admin' : 'User';
    }

    /**
     * Get user's location name based on old PHP system
     */
    public function getLocationName()
    {
        $locationNames = [
            'steinkjer' => 'Steinkjer',
            'namsos' => 'Namsos',
            'lade' => 'Lade',
            'moan' => 'Moan',
            'gramyra' => 'Gramyra',
            'frosta' => 'Frosta',
            'admin' => 'Admin'
        ];

        return $locationNames[$this->username] ?? $this->username;
    }
}
