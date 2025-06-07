<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OpeningHours extends Model
{
    use HasFactory;

    protected $table = 'apningstid';

    protected $fillable = [
        'day',
        'opennamsos',
        'closenamsos',
        'statusnamsos',
        'notesnamsos',
        'openlade',
        'closelade',
        'statuslade',
        'noteslade',
        'openmoan',
        'closemoan',
        'statusmoan',
        'notesmoan',
        'opengramyra',
        'closegramyra',
        'statusgramyra',
        'notesgramyra',
        'openfrosta',
        'closefrosta',
        'statusfrosta',
        'notesfrosta',
        'openhell',
        'closehell',
        'statushell',
        'noteshell',
    ];

    /**
     * Get opening time for a specific location.
     */
    public function getOpenTime($locationName)
    {
        $field = 'open' . strtolower($locationName);
        return $this->$field ?? null;
    }

    /**
     * Get closing time for a specific location.
     */
    public function getCloseTime($locationName)
    {
        $field = 'close' . strtolower($locationName);
        return $this->$field ?? null;
    }

    /**
     * Get status for a specific location.
     */
    public function getStatus($locationName)
    {
        $field = 'status' . strtolower($locationName);
        return $this->$field ?? 0;
    }

    /**
     * Get notes for a specific location.
     */
    public function getNotes($locationName)
    {
        $field = 'notes' . strtolower($locationName);
        return $this->$field ?? null;
    }

    /**
     * Set status for a specific location.
     */
    public function setStatus($locationName, $status)
    {
        $field = 'status' . strtolower($locationName);
        $this->$field = $status;
        return $this->save();
    }

    /**
     * Get hours for specific location by site ID.
     */
    public static function getHoursForLocation($siteId, $day = null)
    {
        $locationNames = [
            7 => 'namsos',
            4 => 'lade',
            6 => 'moan',
            5 => 'gramyra',
            10 => 'frosta',
            11 => 'hell',
        ];

        $locationName = $locationNames[$siteId] ?? null;
        if (!$locationName) {
            return null;
        }

        $query = self::query();
        if ($day) {
            $query->where('day', $day);
        }

        return $query->first();
    }
}
