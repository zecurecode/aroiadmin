<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

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
        'name',
        'email',
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
        ];
    }

    /**
     * Get the location associated with the user.
     */
    public function location()
    {
        return $this->belongsTo(Location::class, 'siteid', 'site_id');
    }

    /**
     * Get orders for this user's location.
     */
    public function orders()
    {
        return $this->hasMany(Order::class, 'site', 'siteid');
    }

    /**
     * Get the username field for authentication.
     */
    public function getAuthIdentifierName()
    {
        return 'username';
    }

    /**
     * Check if user is admin.
     */
    public function isAdmin()
    {
        return $this->role === 'admin';
    }

    /**
     * Check if user can access admin functions.
     */
    public function canAccessAdmin()
    {
        return $this->isAdmin();
    }

    /**
     * Get the site associated with this user.
     */
    public function site()
    {
        return $this->belongsTo(Site::class, 'siteid', 'site_id');
    }
}
