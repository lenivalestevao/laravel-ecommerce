<?php

namespace App\Models;

use Database\Factories\CouponFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Coupon extends Model
{
    /** @use HasFactory<CouponFactory> */
    use HasFactory;

    public const PERCENT = 'percent';
    public const FIXED = 'fixed';

    protected $fillable = [
        'code', 'type', 'value', 'min_subtotal', 'max_uses', 'used_count', 'expires_at', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(fn (Coupon $coupon) => $coupon->code = Str::upper(trim($coupon->code)));
    }

    /**
     * Returns an error message when the coupon cannot be used, or null when it is valid.
     */
    public function problemFor(int $subtotal): ?string
    {
        return match (true) {
            ! $this->is_active => 'This coupon is not active.',
            $this->expires_at !== null && $this->expires_at->isPast() => 'This coupon has expired.',
            $this->max_uses !== null && $this->used_count >= $this->max_uses => 'This coupon has reached its usage limit.',
            $subtotal < $this->min_subtotal => 'Your cart does not reach the minimum amount for this coupon.',
            default => null,
        };
    }

    /** Discount in cents for the given subtotal (never more than the subtotal). */
    public function discountFor(int $subtotal): int
    {
        $discount = $this->type === self::PERCENT
            ? (int) round($subtotal * $this->value / 100)
            : $this->value;

        return min($discount, $subtotal);
    }
}
