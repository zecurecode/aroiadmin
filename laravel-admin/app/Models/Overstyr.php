<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Overstyr extends Model
{
    use HasFactory;

    protected $table = 'overstyr';

    public $timestamps = false; // The original table uses only timestamp, not Laravel's created_at/updated_at

    protected $fillable = [
        'vognid',
        'status',
        'timestamp',
    ];

    protected $casts = [
        'vognid' => 'integer',
        'status' => 'integer',
        'timestamp' => 'datetime',
    ];

    protected $dates = [
        'timestamp',
    ];

    /**
     * Get the latest override for a specific vognid.
     */
    public static function getLatestForVogn($vognid)
    {
        return static::where('vognid', $vognid)
            ->latest('timestamp')
            ->first();
    }

    /**
     * Create a new override entry.
     */
    public static function createOverride($vognid, $status)
    {
        return static::create([
            'vognid' => $vognid,
            'status' => $status,
            'timestamp' => now(),
        ]);
    }

    /**
     * Get all overrides for today.
     */
    public static function getTodayOverrides()
    {
        return static::whereDate('timestamp', today())
            ->orderBy('timestamp', 'desc')
            ->get();
    }
}
