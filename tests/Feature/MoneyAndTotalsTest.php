<?php

namespace Tests\Feature;

use App\Models\Coupon;
use App\Support\Money;
use App\Support\Totals;
use Tests\TestCase;

class MoneyAndTotalsTest extends TestCase
{
    public function test_money_converts_between_cents_and_text(): void
    {
        $this->assertSame(1990, Money::toCents('19.90'));
        $this->assertSame(1990, Money::toCents('19,90'));
        $this->assertSame(0, Money::toCents(''));
        $this->assertSame('19.90', Money::toInput(1990));
        $this->assertSame('$1,234.50', Money::format(123450));
    }

    public function test_shipping_is_free_over_the_threshold(): void
    {
        config(['shop.shipping_fee' => 800, 'shop.free_shipping_over' => 10000, 'shop.tax_rate' => 0]);

        $this->assertSame(800, Totals::calculate(5000)->shipping);
        $this->assertSame(0, Totals::calculate(10000)->shipping);
        $this->assertSame(0, Totals::calculate(0)->shipping);
    }

    public function test_tax_is_applied_after_the_discount(): void
    {
        config(['shop.shipping_fee' => 0, 'shop.free_shipping_over' => 1, 'shop.tax_rate' => 10]);

        $coupon = new Coupon(['type' => Coupon::FIXED, 'value' => 1000]);
        $totals = Totals::calculate(10000, $coupon);

        $this->assertSame(1000, $totals->discount);
        $this->assertSame(900, $totals->tax);
        $this->assertSame(9900, $totals->total);
    }

    public function test_discount_never_exceeds_the_subtotal(): void
    {
        $coupon = new Coupon(['type' => Coupon::FIXED, 'value' => 99999]);

        $this->assertSame(2000, $coupon->discountFor(2000));
    }
}
