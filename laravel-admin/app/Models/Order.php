<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'fornavn',
        'etternavn',
        'telefon',
        'ordreid',
        'ordrestatus',
        'epost',
        'curl',
        'site',
        'paid',
        'sms',
        'curltime',
        'datetime',
    ];

    protected $casts = [
        'paid' => 'boolean',
        'sms' => 'boolean',
        'ordrestatus' => 'integer',
        'curl' => 'integer',
        'site' => 'integer',
        'ordreid' => 'integer',
        'datetime' => 'datetime',
        'curltime' => 'datetime',
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
        return $query->where('ordrestatus', 0);
    }

    /**
     * Scope for orders that haven't been sent to POS.
     */
    public function scopeNotSentToPOS($query)
    {
        return $query->where('curl', 0);
    }

    /**
     * Get the full customer name.
     */
    public function getFullNameAttribute()
    {
        return $this->fornavn . ' ' . $this->etternavn;
    }
}
