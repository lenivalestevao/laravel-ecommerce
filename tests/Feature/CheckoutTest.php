<?php

namespace Tests\Feature;

use App\Mail\NewOrderAlert;
use App\Mail\OrderPlaced;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use RefreshDatabase;

    private function details(array $overrides = []): array
    {
        return array_merge([
            'customer_name' => 'Jane Buyer',
            'customer_email' => 'jane@example.com',
            'customer_phone' => '555-0100',
            'address' => '1 Main Street',
            'city' => 'Springfield',
            'state' => 'IL',
            'postcode' => '62701',
            'country' => 'United States',
            'payment_method' => 'cod',
        ], $overrides);
    }

    public function test_empty_cart_redirects_to_the_cart_page(): void
    {
        $this->get(route('checkout.create'))->assertRedirect(route('cart.index'));
    }

    public function test_guest_can_place_a_cash_on_delivery_order(): void
    {
        Mail::fake();
        $product = Product::factory()->create(['price' => 5000, 'stock' => 10]);

        $this->post(route('cart.add', $product), ['quantity' => 2]);
        $response = $this->post(route('checkout.store'), $this->details());

        $order = Order::firstOrFail();
        $response->assertRedirect(route('orders.show', $order));

        $this->assertSame(10000, $order->total);
        $this->assertSame('pending', $order->status);
        $this->assertSame('unpaid', $order->payment_status);
        $this->assertSame(8, $product->fresh()->stock);
        $this->assertDatabaseHas('order_items', ['order_id' => $order->id, 'product_id' => $product->id, 'quantity' => 2, 'total' => 10000]);
        $this->assertEmpty(session('cart.items', []));

        Mail::assertSent(OrderPlaced::class);
        Mail::assertSent(NewOrderAlert::class);

        // The buyer's session may open the order page; another visitor may not.
        $this->get(route('orders.show', $order))->assertOk()->assertSee($order->number);
        $this->flushSession();
        $this->get(route('orders.show', $order))->assertForbidden();
    }

    public function test_order_keeps_the_price_paid_even_if_the_product_changes_later(): void
    {
        Mail::fake();
        $product = Product::factory()->create(['price' => 3000, 'stock' => 5, 'name' => 'Original Name']);

        $this->post(route('cart.add', $product));
        $this->post(route('checkout.store'), $this->details());

        $product->update(['price' => 9999, 'name' => 'Renamed']);

        $item = Order::firstOrFail()->items()->firstOrFail();
        $this->assertSame(3000, $item->price);
        $this->assertSame('Original Name', $item->name);
    }

    public function test_coupon_is_recorded_and_counted(): void
    {
        Mail::fake();
        $product = Product::factory()->create(['price' => 20000]);
        $coupon = Coupon::factory()->create(['code' => 'TEN', 'value' => 10]);

        $this->post(route('cart.add', $product));
        $this->post(route('cart.coupon.apply'), ['code' => 'TEN']);
        $this->post(route('checkout.store'), $this->details());

        $order = Order::firstOrFail();
        $this->assertSame(2000, $order->discount);
        $this->assertSame($coupon->id, $order->coupon_id);
        $this->assertSame(1, $coupon->fresh()->used_count);
    }

    public function test_checkout_requires_valid_details(): void
    {
        $product = Product::factory()->create();
        $this->post(route('cart.add', $product));

        $this->post(route('checkout.store'), [])
            ->assertSessionHasErrors(['customer_name', 'customer_email', 'address', 'payment_method']);

        $this->post(route('checkout.store'), $this->details(['payment_method' => 'stripe']))
            ->assertSessionHasErrors('payment_method'); // Stripe is off without keys

        $this->assertSame(0, Order::count());
    }

    public function test_cannot_buy_more_than_the_stock_left(): void
    {
        $product = Product::factory()->create(['stock' => 5]);
        $this->post(route('cart.add', $product), ['quantity' => 3]);

        $product->update(['stock' => 1]); // someone else bought most of it

        $this->post(route('checkout.store'), $this->details())->assertSessionHasErrors('cart');

        $this->assertSame(0, Order::count());
        $this->assertSame(1, $product->fresh()->stock);
    }

    public function test_logged_in_customer_sees_the_order_in_their_account(): void
    {
        Mail::fake();
        $user = User::factory()->create();
        $product = Product::factory()->create();

        $this->actingAs($user)->post(route('cart.add', $product));
        $this->actingAs($user)->post(route('checkout.store'), $this->details());

        $order = Order::firstOrFail();
        $this->assertSame($user->id, $order->user_id);
        $this->actingAs($user)->get(route('account.index'))->assertSee($order->number);
    }

    public function test_guest_can_track_an_order_with_number_and_email(): void
    {
        $order = Order::factory()->create(['customer_email' => 'jane@example.com']);

        $this->post(route('track.lookup'), ['number' => $order->number, 'email' => 'wrong@example.com'])
            ->assertSessionHasErrors('number');

        $this->post(route('track.lookup'), ['number' => $order->number, 'email' => 'jane@example.com'])
            ->assertRedirect(route('orders.show', $order));
    }
}
