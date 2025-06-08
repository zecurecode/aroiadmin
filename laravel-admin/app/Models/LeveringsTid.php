<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeveringsTid extends Model
{
    use HasFactory;

    protected $table = 'leveringstid';
    public $timestamps = false; // The original table doesn't have timestamps

    protected $fillable = [
        'tid'
    ];

    /**
     * Get all available delivery times.
     */
    public static function getAvailableTimes()
    {
        return static::orderBy('tid')->pluck('tid', 'id');
    }

    /**
     * Get delivery time by ID.
     */
    public static function getTimeById($id)
    {
        $time = static::find($id);
        return $time ? $time->tid : null;
    }
}
