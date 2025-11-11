<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OpeningHours extends Model
{
    use HasFactory;

    protected $table = 'apningstid';

    public $timestamps = false; // The original table doesn't have timestamps

    protected $fillable = [
        'userid',
        'day',
        'openlade',
        'closelade',
        'noteslade',
        'openmoan',
        'closemoan',
        'notesmoan',
        'opennamsos',
        'closenamsos',
        'notesnamsos',
        'opengramyra',
        'closegramyra',
        'notesgramyra',
        'openfrosta',
        'closefrosta',
        'notesfrosta',
        'openhell',
        'closehell',
        'noteshell',
        'opensteinkjer',
        'closesteinkjer',
        'notessteinkjer',
        'statuslade',
        'statusmoan',
        'statusgramyra',
        'statusnamsos',
        'statusfrosta',
        'statushell',
        'statussteinkjer',
        'btnmoan',
        'btnlade',
        'btngramyra',
        'btnnamsos',
        'btnfrosta',
        'btnhell',
        'btbsteinkjer',
    ];

    protected $casts = [
        'userid' => 'integer',
        'statuslade' => 'integer',
        'statusmoan' => 'integer',
        'statusgramyra' => 'integer',
        'statusnamsos' => 'integer',
        'statusfrosta' => 'integer',
        'statushell' => 'integer',
        'statussteinkjer' => 'integer',
        'btnmoan' => 'integer',
        'btnlade' => 'integer',
        'btngramyra' => 'integer',
        'btnnamsos' => 'integer',
        'btnfrosta' => 'integer',
        'btnhell' => 'integer',
        'btbsteinkjer' => 'integer',
    ];

    /**
     * Get the user that owns the opening hours.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'userid');
    }

    /**
     * Get opening time for a specific location.
     */
    public function getOpenTime($locationName)
    {
        $field = 'open'.strtolower($locationName);

        return $this->$field ?? null;
    }

    /**
     * Get closing time for a specific location.
     */
    public function getCloseTime($locationName)
    {
        $field = 'close'.strtolower($locationName);

        return $this->$field ?? null;
    }

    /**
     * Get status for a specific location.
     */
    public function getStatus($locationName)
    {
        $field = 'status'.strtolower($locationName);

        return $this->$field ?? 0;
    }

    /**
     * Get notes for a specific location.
     */
    public function getNotes($locationName)
    {
        $field = 'notes'.strtolower($locationName);

        return $this->$field ?? null;
    }

    /**
     * Get button status for a specific location.
     */
    public function getButtonStatus($locationName)
    {
        $field = 'btn'.strtolower($locationName);
        if ($locationName === 'steinkjer') {
            $field = 'btb'.strtolower($locationName);
        }

        return $this->$field ?? 0;
    }

    /**
     * Set status for a specific location.
     */
    public function setStatus($locationName, $status)
    {
        $field = 'status'.strtolower($locationName);
        $this->$field = $status;

        return $this->save();
    }

    /**
     * Get hours for specific location by site ID.
     */
    public static function getHoursForLocation($siteId, $day = null)
    {
        // Try to get location name from database first
        $site = \App\Models\Site::findBySiteId($siteId);
        $locationName = $site ? strtolower($site->name) : null;

        // Fallback to hardcoded mapping
        if (! $locationName) {
            $locationNames = [
                7 => 'namsos',
                4 => 'lade',
                6 => 'moan',
                5 => 'gramyra',
                10 => 'frosta',
                11 => 'hell',
                13 => 'steinkjer',
            ];
            $locationName = $locationNames[$siteId] ?? null;
        }

        if (! $locationName) {
            return null;
        }

        $query = self::query();
        if ($day) {
            $query->where('day', $day);
        }

        return $query->first();
    }

    /**
     * Get all available locations.
     */
    public static function getAvailableLocations()
    {
        return [
            'lade' => 'Lade',
            'moan' => 'Moan',
            'namsos' => 'Namsos',
            'gramyra' => 'Gramyra',
            'frosta' => 'Frosta',
            'hell' => 'Hell',
            'steinkjer' => 'Steinkjer',
        ];
    }
}
