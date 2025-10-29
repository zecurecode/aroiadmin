<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    use HasFactory;

    protected $fillable = [
        'site_id',
        'name',
        'group_name',
        'license',
        'phone',
        'email',
        'address',
        'order_url',
        'active',
        'display_order',
        'delivery_time_minutes',
    ];

    protected $casts = [
        'site_id' => 'integer',
        'license' => 'integer',
        'active' => 'boolean',
        'display_order' => 'integer',
        'delivery_time_minutes' => 'integer',
    ];

    /**
     * Get the users for this location.
     */
    public function users()
    {
        return $this->hasMany(User::class, 'siteid', 'site_id');
    }

    /**
     * Get the orders for this location.
     */
    public function orders()
    {
        return $this->hasMany(Order::class, 'site', 'site_id');
    }

    /**
     * Get the opening hours for this location.
     */
    public function openingHours()
    {
        return $this->hasMany(OpeningHours::class, 'location_site_id', 'site_id');
    }

    /**
     * Scope for active locations.
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    /**
     * Get location name by site ID.
     */
    public static function getNameBySiteId($siteId)
    {
        // Try to get from sites table first
        $site = \App\Models\Site::findBySiteId($siteId);
        if ($site) {
            return $site->name;
        }

        // Fallback to hardcoded values for backward compatibility
        $names = [
            7 => 'Namsos',
            4 => 'Lade',
            6 => 'Moan',
            5 => 'Gramyra',
            10 => 'Frosta',
            11 => 'Hell',
            13 => 'Steinkjer',
        ];

        return $names[$siteId] ?? 'Unknown';
    }

    /**
     * Get today's opening hours.
     */
    public function getTodayOpeningHours()
    {
        $day = now()->format('l'); // Monday, Tuesday, etc.
        return $this->openingHours()->where('day', $day)->first();
    }

    /**
     * Get catering settings for this location.
     */
    public function cateringSettings()
    {
        return $this->hasOne(CateringSettings::class, 'site_id', 'site_id');
    }

    /**
     * Scope for locations in a specific group.
     */
    public function scopeInGroup($query, $group)
    {
        return $query->where('group_name', $group);
    }

    /**
     * Scope for ordering by display order and name.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('display_order', 'asc')
                    ->orderBy('name', 'asc');
    }

    /**
     * Get all unique group names.
     */
    public static function getGroups()
    {
        return self::whereNotNull('group_name')
                   ->distinct()
                   ->pluck('group_name')
                   ->sort()
                   ->values();
    }
}
