<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Site extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'site_id',
        'url',
        'consumer_key',
        'consumer_secret',
        'license',
        'active',
    ];

    protected $casts = [
        'site_id' => 'integer',
        'license' => 'integer',
        'active' => 'boolean',
    ];

    /**
     * Get users associated with this site.
     */
    public function users()
    {
        return $this->hasMany(User::class, 'siteid', 'site_id');
    }

    /**
     * Get orders for this site.
     */
    public function orders()
    {
        return $this->hasMany(Order::class, 'site', 'site_id');
    }

    /**
     * Get site by site_id.
     */
    public static function findBySiteId($siteId)
    {
        return static::where('site_id', $siteId)->first();
    }
}
