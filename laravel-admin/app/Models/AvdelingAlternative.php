<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AvdelingAlternative extends Model
{
    use HasFactory;

    protected $table = '_avdeling';

    public $timestamps = false; // The original table doesn't have timestamps

    protected $primaryKey = 'Id';

    protected $fillable = [
        'Id',
        'Navn',
        'Tlf',
        'Epost',
        'SiteID',
        'Aktiv',
        'Url',
        'APIKey',
        'APISecret',
        'APIUrl',
    ];

    protected $casts = [
        'Id' => 'integer',
        'SiteID' => 'integer',
        'Aktiv' => 'boolean',
    ];

    /**
     * Get the opening hours for this avdeling.
     */
    public function openingHours()
    {
        return $this->hasOne(ApningstidAlternative::class, 'AvdID', 'Id');
    }

    /**
     * Scope for active avdeling.
     */
    public function scopeActive($query)
    {
        return $query->where('Aktiv', true);
    }

    /**
     * Scope for inactive avdeling.
     */
    public function scopeInactive($query)
    {
        return $query->where('Aktiv', false);
    }

    /**
     * Check if avdeling is active.
     */
    public function isActive()
    {
        return $this->Aktiv;
    }

    /**
     * Get avdeling by site ID.
     */
    public static function findBySiteId($siteId)
    {
        return static::where('SiteID', $siteId)->first();
    }

    /**
     * Get API credentials.
     */
    public function getApiCredentials()
    {
        return [
            'key' => $this->APIKey,
            'secret' => $this->APISecret,
            'url' => $this->APIUrl,
        ];
    }

    /**
     * Check if has API credentials.
     */
    public function hasApiCredentials()
    {
        return ! empty($this->APIKey) && ! empty($this->APISecret) && ! empty($this->APIUrl);
    }
}
