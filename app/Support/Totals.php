<?php

namespace App\Support;

use App\Models\Coupon;

/**
 * Immutable order amounts, all in cents.
 */
final readonly class Totals
{
    public function __construct(
        public int $subtotal,
        public int $discount,
        public int $shipping,
        public int $tax,
        public int $total,
    ) {
    }

    public static function calculate(int $subtotal, ?Coupon $coupon = null): self
    {
        $discount = $coupon?->discountFor($subtotal) ?? 0;
        $afterDiscount = $subtotal - $discount;

        $shipping = $subtotal === 0 || $afterDiscount >= config('shop.free_shipping_over')
            ? 0
            : (int) config('shop.shipping_fee');

        $tax = (int) round($afterDiscount * (float) config('shop.tax_rate') / 100);

        return new self($subtotal, $discount, $shipping, $tax, $afterDiscount + $shipping + $tax);
    }
}
