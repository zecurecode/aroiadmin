<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CateringSettings extends Model
{
    protected $fillable = [
        'site_id',
        'catering_email',
        'catering_enabled',
        'min_guests',
        'advance_notice_days',
        'min_order_amount',
        'catering_info',
        'blocked_dates'
    ];

    protected $casts = [
        'catering_enabled' => 'boolean',
        'min_guests' => 'integer',
        'advance_notice_days' => 'integer',
        'min_order_amount' => 'decimal:2',
        'blocked_dates' => 'array'
    ];

    public function location()
    {
        return $this->belongsTo(Location::class, 'site_id', 'site_id');
    }

    public function isDateBlocked($date)
    {
        if (!$this->blocked_dates) {
            return false;
        }
        
        return in_array($date, $this->blocked_dates);
    }

    public function addBlockedDate($date)
    {
        $blocked = $this->blocked_dates ?? [];
        if (!in_array($date, $blocked)) {
            $blocked[] = $date;
            $this->blocked_dates = $blocked;
            $this->save();
        }
    }

    public function removeBlockedDate($date)
    {
        $blocked = $this->blocked_dates ?? [];
        $blocked = array_values(array_diff($blocked, [$date]));
        $this->blocked_dates = $blocked;
        $this->save();
    }
}
