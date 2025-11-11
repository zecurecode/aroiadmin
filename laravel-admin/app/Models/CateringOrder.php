<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CateringOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'location_id',
        'site_id',
        'order_number',
        'delivery_date',
        'delivery_time',
        'delivery_address',
        'number_of_guests',
        'contact_name',
        'contact_phone',
        'contact_email',
        'company_name',
        'company_org_number',
        'invoice_address',
        'invoice_email',
        'special_requirements',
        'catering_notes',
        'products',
        'total_amount',
        'status',
        'catering_email',
        'invoice_sent_at',
        'invoice_paid_at',
        'cancelled_at',
        'cancellation_reason',
    ];

    protected $casts = [
        'products' => 'array',
        'delivery_date' => 'date',
        'total_amount' => 'decimal:2',
        'number_of_guests' => 'integer',
        'invoice_sent_at' => 'datetime',
        'invoice_paid_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    /**
     * Status options
     */
    const STATUS_PENDING = 'pending';

    const STATUS_CONFIRMED = 'confirmed';

    const STATUS_PREPARING = 'preparing';

    const STATUS_READY = 'ready';

    const STATUS_DELIVERED = 'delivered';

    const STATUS_CANCELLED = 'cancelled';

    /**
     * Get the location
     */
    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    /**
     * Get status label
     */
    public function getStatusLabelAttribute()
    {
        $labels = [
            self::STATUS_PENDING => 'Venter',
            self::STATUS_CONFIRMED => 'Bekreftet',
            self::STATUS_PREPARING => 'Under forberedelse',
            self::STATUS_READY => 'Klar',
            self::STATUS_DELIVERED => 'Levert',
            self::STATUS_CANCELLED => 'Kansellert',
        ];

        return $labels[$this->status] ?? $this->status;
    }

    /**
     * Get status color
     */
    public function getStatusColorAttribute()
    {
        $colors = [
            self::STATUS_PENDING => 'warning',
            self::STATUS_CONFIRMED => 'info',
            self::STATUS_PREPARING => 'primary',
            self::STATUS_READY => 'success',
            self::STATUS_DELIVERED => 'secondary',
            self::STATUS_CANCELLED => 'danger',
        ];

        return $colors[$this->status] ?? 'secondary';
    }

    /**
     * Scope for pending orders
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope for confirmed orders
     */
    public function scopeConfirmed($query)
    {
        return $query->where('status', self::STATUS_CONFIRMED);
    }

    /**
     * Scope for active orders (not cancelled or delivered)
     */
    public function scopeActive($query)
    {
        return $query->whereNotIn('status', [self::STATUS_CANCELLED, self::STATUS_DELIVERED]);
    }

    /**
     * Scope for upcoming deliveries
     */
    public function scopeUpcoming($query)
    {
        return $query->where('delivery_date', '>=', now()->toDateString())
            ->orderBy('delivery_date')
            ->orderBy('delivery_time');
    }

    /**
     * Format products for display
     */
    public function getFormattedProductsAttribute()
    {
        if (! $this->products) {
            return [];
        }

        return collect($this->products)->map(function ($product) {
            return (object) [
                'name' => $product['name'] ?? '',
                'quantity' => $product['quantity'] ?? 0,
                'price' => $product['price'] ?? 0,
                'total' => ($product['quantity'] ?? 0) * ($product['price'] ?? 0),
            ];
        });
    }

    /**
     * Get formatted delivery datetime
     */
    public function getDeliveryDatetimeAttribute()
    {
        return $this->delivery_date->format('d.m.Y').' kl. '.$this->delivery_time;
    }

    /**
     * Check if order can be cancelled
     */
    public function canBeCancelled()
    {
        return ! in_array($this->status, [self::STATUS_CANCELLED, self::STATUS_DELIVERED])
            && $this->delivery_date->isAfter(now());
    }

    /**
     * Cancel the order
     */
    public function cancel($reason = null)
    {
        $this->update([
            'status' => self::STATUS_CANCELLED,
            'cancelled_at' => now(),
            'cancellation_reason' => $reason,
        ]);
    }
}
