<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CateringSettings extends Model
{
    use HasFactory;

    protected $fillable = [
        'site_id',
        'catering_email',
        'catering_enabled',
        'min_guests',
        'advance_notice_days',
        'min_order_amount',
        'catering_info',
        'blocked_dates',
        'delivery_times',
        'delivery_areas',
    ];

    protected $casts = [
        'catering_enabled' => 'boolean',
        'min_guests' => 'integer',
        'advance_notice_days' => 'integer',
        'min_order_amount' => 'decimal:2',
        'blocked_dates' => 'array',
        'delivery_times' => 'array',
        'delivery_areas' => 'array',
    ];

    /**
     * Get the location
     */
    public function location()
    {
        return $this->belongsTo(Location::class, 'site_id', 'site_id');
    }

    /**
     * Get default delivery times
     */
    public function getDeliveryTimesAttribute($value)
    {
        if ($value) {
            return $value;
        }

        // Default delivery times
        return [
            '10:00',
            '11:00',
            '12:00',
            '13:00',
            '14:00',
            '15:00',
            '16:00',
            '17:00',
            '18:00',
            '19:00',
            '20:00',
        ];
    }

    /**
     * Check if a date is blocked
     */
    public function isDateBlocked($date)
    {
        $dateString = $date instanceof \Carbon\Carbon ? $date->format('Y-m-d') : $date;

        return in_array($dateString, $this->blocked_dates ?? []);
    }

    /**
     * Add a blocked date
     */
    public function blockDate($date)
    {
        $dateString = $date instanceof \Carbon\Carbon ? $date->format('Y-m-d') : $date;
        $blockedDates = $this->blocked_dates ?? [];

        if (! in_array($dateString, $blockedDates)) {
            $blockedDates[] = $dateString;
            $this->update(['blocked_dates' => $blockedDates]);
        }
    }

    /**
     * Remove a blocked date
     */
    public function unblockDate($date)
    {
        $dateString = $date instanceof \Carbon\Carbon ? $date->format('Y-m-d') : $date;
        $blockedDates = $this->blocked_dates ?? [];

        $blockedDates = array_values(array_diff($blockedDates, [$dateString]));
        $this->update(['blocked_dates' => $blockedDates]);
    }
}
