<?php

namespace App\Models;

use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use HasFactory;

    protected $fillable = [
        'number', 'user_id', 'coupon_id', 'status', 'payment_method', 'payment_status', 'payment_reference', 'paid_at',
        'customer_name', 'customer_email', 'customer_phone',
        'address', 'city', 'state', 'postcode', 'country', 'notes',
        'subtotal', 'discount', 'shipping', 'tax', 'total',
    ];

    protected function casts(): array
    {
        return ['paid_at' => 'datetime'];
    }

    public function getRouteKeyName(): string
    {
        return 'number';
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function statusLabel(): string
    {
        return config('shop.order_statuses.'.$this->status, ucfirst($this->status));
    }

    public function paymentLabel(): string
    {
        return config('shop.payment_methods.'.$this->payment_method, $this->payment_method);
    }

    public function isPaid(): bool
    {
        return $this->payment_status === 'paid';
    }

    /** Marks the order as paid once; safe to call again from repeated webhooks. */
    public function markPaid(?string $reference = null): bool
    {
        if ($this->isPaid()) {
            return false;
        }

        $this->forceFill([
            'payment_status' => 'paid',
            'payment_reference' => $reference ?? $this->payment_reference,
            'paid_at' => now(),
            'status' => $this->status === 'pending' ? 'processing' : $this->status,
        ])->save();

        return true;
    }

    /** Generates a readable unique order number such as LS-2026-000042. */
    public static function nextNumber(): string
    {
        $next = (static::max('id') ?? 0) + 1;

        return sprintf('LS-%s-%06d', now()->format('Y'), $next);
    }
}
