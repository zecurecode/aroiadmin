<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class SpecialHours extends Model
{
    use HasFactory;

    protected $fillable = [
        'location_id',
        'date',
        'end_date',
        'open_time',
        'close_time',
        'is_closed',
        'reason',
        'type',
        'recurring_yearly',
        'notes',
        'created_by'
    ];

    protected $casts = [
        'date' => 'date',
        'end_date' => 'date',
        'open_time' => 'datetime',
        'close_time' => 'datetime',
        'is_closed' => 'boolean',
        'recurring_yearly' => 'boolean',
        'location_id' => 'integer',
        'created_by' => 'integer'
    ];

    /**
     * Get the location this special hours entry belongs to
     */
    public function location()
    {
        return $this->belongsTo(AvdelingAlternative::class, 'location_id', 'Id');
    }

    /**
     * Get the user who created this entry
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Check if this entry covers a specific date
     */
    public function coversDate($date)
    {
        $checkDate = Carbon::parse($date);
        $startDate = Carbon::parse($this->date);

        if ($this->end_date) {
            $endDate = Carbon::parse($this->end_date);
            return $checkDate->between($startDate, $endDate);
        }

        return $checkDate->isSameDay($startDate);
    }

    /**
     * Check if this is a recurring yearly entry that applies to a date
     */
    public function appliesToDate($date)
    {
        if (!$this->recurring_yearly) {
            return $this->coversDate($date);
        }

        $checkDate = Carbon::parse($date);
        $entryDate = Carbon::parse($this->date);

        // For recurring yearly, check if month and day match
        return $checkDate->month === $entryDate->month &&
               $checkDate->day === $entryDate->day;
    }

    /**
     * Get formatted hours display
     */
    public function getFormattedHoursAttribute()
    {
        if ($this->is_closed) {
            return 'Stengt';
        }

        if (!$this->open_time || !$this->close_time) {
            return 'Stengt';
        }

        return Carbon::parse($this->open_time)->format('H:i') . ' - ' .
               Carbon::parse($this->close_time)->format('H:i');
    }

    /**
     * Get type display name
     */
    public function getTypeDisplayAttribute()
    {
        $types = [
            'special' => 'Spesielle åpningstider',
            'holiday' => 'Helligdag',
            'maintenance' => 'Vedlikehold',
            'event' => 'Arrangement',
            'closure' => 'Stengt'
        ];

        return $types[$this->type] ?? ucfirst($this->type);
    }

    /**
     * Scope for active entries (current and future)
     */
    public function scopeActive($query)
    {
        return $query->where('date', '>=', now()->toDateString());
    }

    /**
     * Scope for a specific location
     */
    public function scopeForLocation($query, $locationId)
    {
        return $query->where('location_id', $locationId);
    }

    /**
     * Scope for date range
     */
    public function scopeInDateRange($query, $startDate, $endDate)
    {
        return $query->where(function ($q) use ($startDate, $endDate) {
            $q->whereBetween('date', [$startDate, $endDate])
              ->orWhere(function ($subQ) use ($startDate, $endDate) {
                  $subQ->whereNotNull('end_date')
                       ->where('date', '<=', $endDate)
                       ->where('end_date', '>=', $startDate);
              });
        });
    }
}
