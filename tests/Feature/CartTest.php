<?php

namespace Tests\Feature;

use App\Models\Coupon;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_can_be_added_and_shown_in_the_cart(): void
    {
        $product = Product::factory()->create(['name' => 'Desk Lamp', 'price' => 2500]);

        $this->post(route('cart.add', $product), ['quantity' => 2])->assertSessionHas('success');

        $this->get(route('cart.index'))->assertOk()->assertSee('Desk Lamp')->assertSee('$50.00');
    }

    public function test_quantity_is_capped_at_available_stock(): void
    {
        $product = Product::factory()->create(['stock' => 2]);

        $this->post(route('cart.add', $product), ['quantity' => 5]);

        $this->assertSame([$product->id => 2], session('cart.items'));
    }

    public function test_out_of_stock_product_cannot_be_added(): void
    {
        $product = Product::factory()->outOfStock()->create();

        $this->post(route('cart.add', $product))->assertSessionHas('error');

        $this->assertEmpty(session('cart.items', []));
    }

    public function test_quantity_can_be_updated_and_removed(): void
    {
        $product = Product::factory()->create(['stock' => 10]);
        $this->post(route('cart.add', $product));

        $this->patch(route('cart.update', $product), ['quantity' => 4]);
        $this->assertSame([$product->id => 4], session('cart.items'));

        $this->delete(route('cart.remove', $product));
        $this->assertEmpty(session('cart.items'));
    }

    public function test_percent_coupon_lowers_the_total(): void
    {
        $product = Product::factory()->create(['price' => 10000]);
        Coupon::factory()->create(['code' => 'TEN', 'type' => Coupon::PERCENT, 'value' => 10]);

        $this->post(route('cart.add', $product));
        $this->post(route('cart.coupon.apply'), ['code' => 'ten'])->assertSessionHas('success');

        // 100.00 - 10.00 discount + 8.00 shipping (below the free shipping threshold)
        $this->get(route('cart.index'))->assertSee('TEN')->assertSee('$98.00');
    }

    public function test_unknown_expired_and_minimum_coupons_are_refused(): void
    {
        $product = Product::factory()->create(['price' => 1000]);
        $this->post(route('cart.add', $product));

        $this->post(route('cart.coupon.apply'), ['code' => 'NOPE'])->assertSessionHasErrors('code');

        Coupon::factory()->create(['code' => 'OLD', 'expires_at' => now()->subDay()]);
        $this->post(route('cart.coupon.apply'), ['code' => 'OLD'])->assertSessionHasErrors('code');

        Coupon::factory()->create(['code' => 'BIG', 'min_subtotal' => 50000]);
        $this->post(route('cart.coupon.apply'), ['code' => 'BIG'])->assertSessionHasErrors('code');
    }
}
