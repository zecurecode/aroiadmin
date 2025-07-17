<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    public $timestamps = false; // The original table doesn't have Laravel timestamps

    protected $fillable = [
        'fornavn',
        'etternavn',
        'telefon',
        'ordreid',
        'ordrestatus',
        'curl',
        'curltime',
        'datetime',
        'epost',
        'site',
        'paid',
        'wcstatus',
        'payref',
        'seordre',
        'paymentmethod',
        'hentes',
        'sms',
        'is_catering',
        'delivery_date',
        'delivery_time',
        'delivery_address',
        'number_of_guests',
        'special_requirements',
        'catering_notes',
        'catering_status',
        'catering_email'
    ];

    protected $casts = [
        'ordreid' => 'integer',
        'site' => 'integer',
        'paid' => 'boolean',
        'seordre' => 'integer',
        'sms' => 'integer',
        'datetime' => 'datetime',
        'curltime' => 'datetime',
        'is_catering' => 'boolean',
        'delivery_date' => 'date',
        'delivery_time' => 'string',
        'number_of_guests' => 'integer',
    ];

    /**
     * The attributes that should be cast to native types.
     */
    protected $dates = [
        'datetime',
        'curltime'
    ];

    /**
     * Get the location for this order.
     */
    public function location()
    {
        return $this->belongsTo(Location::class, 'site', 'site_id');
    }

    /**
     * Get the user responsible for this location.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'site', 'siteid');
    }

    /**
     * Get the department/avdeling for this order.
     */
    public function avdeling()
    {
        return $this->belongsTo(Avdeling::class, 'site', 'siteid');
    }

    /**
     * Scope for unpaid orders.
     */
    public function scopeUnpaid($query)
    {
        return $query->where('paid', false);
    }

    /**
     * Scope for pending orders.
     */
    public function scopePending($query)
    {
        return $query->where('ordrestatus', '1');
    }

    /**
     * Scope for orders that haven't been sent to POS.
     */
    public function scopeNotSentToPOS($query)
    {
        return $query->where('curl', '');
    }

    /**
     * Scope for orders that need to be seen.
     */
    public function scopeNeedToSee($query)
    {
        return $query->where('seordre', 0);
    }

    /**
     * Scope for orders that haven't received SMS.
     */
    public function scopeNoSMS($query)
    {
        return $query->where('sms', 0);
    }

    /**
     * Get the full customer name.
     */
    public function getFullNameAttribute()
    {
        return $this->fornavn . ' ' . $this->etternavn;
    }

    /**
     * Check if order is paid.
     */
    public function isPaid()
    {
        return $this->paid == 1;
    }

    /**
     * Check if order has been sent to POS.
     */
    public function isSentToPOS()
    {
        return !empty($this->curl) && $this->curl !== '0';
    }

    /**
     * Check if SMS has been sent.
     */
    public function hasSMSBeenSent()
    {
        return $this->sms == 1;
    }

    /**
     * Mark order as seen.
     */
    public function markAsSeen()
    {
        return $this->update(['seordre' => 1]);
    }

    /**
     * Mark SMS as sent.
     */
    public function markSMSAsSent()
    {
        return $this->update(['sms' => 1]);
    }

    /**
     * Scope for catering orders.
     */
    public function scopeCatering($query)
    {
        return $query->where('is_catering', true);
    }

    /**
     * Scope for regular orders.
     */
    public function scopeRegular($query)
    {
        return $query->where('is_catering', false);
    }

    /**
     * Check if order is catering.
     */
    public function isCatering()
    {
        return $this->is_catering;
    }

    /**
     * Get catering settings for this order's location.
     */
    public function cateringSettings()
    {
        return CateringSettings::where('site_id', $this->site)->first();
    }

    /**
     * Update catering status.
     */
    public function updateCateringStatus($status)
    {
        return $this->update(['catering_status' => $status]);
    }
}
