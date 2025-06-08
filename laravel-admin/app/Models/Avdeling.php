<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Avdeling extends Model
{
    use HasFactory;

    protected $table = 'avdeling';
    public $timestamps = false; // The original table doesn't have timestamps

    protected $fillable = [
        'navn',
        'tlf',
        'geo',
        'siteid',
        'inaktivert',
        'deaktivert_tekst',
        'url'
    ];

    protected $casts = [
        'siteid' => 'integer',
        'inaktivert' => 'boolean',
    ];

    /**
     * Get the users for this avdeling.
     */
    public function users()
    {
        return $this->hasMany(User::class, 'siteid', 'siteid');
    }

    /**
     * Get the orders for this avdeling.
     */
    public function orders()
    {
        return $this->hasMany(Order::class, 'site', 'siteid');
    }

    /**
     * Get the opening hours for this avdeling.
     */
    public function openingHours()
    {
        return $this->hasMany(OpeningHours::class, 'userid', 'siteid');
    }

    /**
     * Scope for active avdeling.
     */
    public function scopeActive($query)
    {
        return $query->where('inaktivert', false);
    }

    /**
     * Scope for inactive avdeling.
     */
    public function scopeInactive($query)
    {
        return $query->where('inaktivert', true);
    }

    /**
     * Check if avdeling is active.
     */
    public function isActive()
    {
        return !$this->inaktivert;
    }

    /**
     * Get avdeling by site ID.
     */
    public static function findBySiteId($siteId)
    {
        return static::where('siteid', $siteId)->first();
    }
}
