<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * The gateways are tested with faked HTTP responses. Before going live, also run a real
 * payment in each provider's sandbox: these tests cannot prove the live APIs still match.
 */
class PaymentTest extends TestCase
{
    use RefreshDatabase;

    private function order(string $method, array $attributes = []): Order
    {
        $order = Order::factory()->create(array_merge(['payment_method' => $method, 'total' => 5000], $attributes));
        $order->items()->create(['name' => 'Thing', 'sku' => 'T-1', 'price' => 5000, 'quantity' => 1, 'total' => 5000]);

        return $order;
    }

    private function checkoutWith(string $method)
    {
        Mail::fake();
        $this->post(route('cart.add', Product::factory()->create(['price' => 5000])));

        return $this->post(route('checkout.store'), [
            'customer_name' => 'Jane', 'customer_email' => 'jane@example.com',
            'address' => '1 Main St', 'city' => 'Town', 'postcode' => '12345', 'country' => 'US',
            'payment_method' => $method,
        ]);
    }

    public function test_gateways_are_hidden_until_configured(): void
    {
        $this->get(route('checkout.create'))->assertRedirect(); // empty cart, just proves the page guard

        $this->post(route('cart.add', Product::factory()->create()));
        $this->get(route('checkout.create'))->assertDontSee('Stripe')->assertDontSee('PayPal')->assertDontSee('Mercado Pago')
            ->assertSee('Cash on delivery');

        config(['services.stripe.secret' => 'sk_test_x', 'services.paypal.client_id' => 'id', 'services.paypal.secret' => 's', 'services.mercadopago.access_token' => 'tok']);

        $this->get(route('checkout.create'))->assertSee('Stripe')->assertSee('PayPal')->assertSee('Mercado Pago');
    }

    public function test_stripe_checkout_redirects_to_the_hosted_page_and_confirms_payment(): void
    {
        config(['services.stripe.secret' => 'sk_test_x']);
        Http::fake([
            'api.stripe.com/v1/checkout/sessions' => Http::response(['id' => 'cs_1', 'url' => 'https://checkout.stripe.test/pay']),
        ]);

        $this->checkoutWith('stripe')->assertRedirect('https://checkout.stripe.test/pay');

        $order = Order::firstOrFail();
        $this->assertSame('cs_1', $order->payment_reference);
        $this->assertFalse($order->isPaid());

        Http::fake([
            'api.stripe.com/v1/checkout/sessions/cs_1' => Http::response(['payment_status' => 'paid', 'payment_intent' => 'pi_1', 'client_reference_id' => $order->number]),
        ]);

        $this->get(route('payment.return', [$order, 'gateway' => 'stripe', 'session_id' => 'cs_1']))
            ->assertRedirect(route('orders.show', $order))
            ->assertSessionHas('success');

        $this->assertTrue($order->fresh()->isPaid());
        $this->assertSame('processing', $order->fresh()->status);
    }

    public function test_stripe_return_does_not_mark_unpaid_sessions_as_paid(): void
    {
        config(['services.stripe.secret' => 'sk_test_x']);
        $order = $this->order('stripe', ['payment_reference' => 'cs_2']);
        Http::fake(['api.stripe.com/*' => Http::response(['payment_status' => 'unpaid', 'client_reference_id' => $order->number])]);

        $this->withSession(['my_orders' => [$order->number]])
            ->get(route('payment.return', [$order, 'gateway' => 'stripe']))
            ->assertSessionHas('error');

        $this->assertFalse($order->fresh()->isPaid());
    }

    public function test_stripe_webhook_requires_a_valid_signature(): void
    {
        config(['services.stripe.secret' => 'sk_test_x', 'services.stripe.webhook_secret' => 'whsec_test']);
        $order = $this->order('stripe');

        $payload = json_encode(['type' => 'checkout.session.completed', 'data' => ['object' => [
            'id' => 'cs_9', 'payment_status' => 'paid', 'client_reference_id' => $order->number, 'payment_intent' => 'pi_9',
        ]]]);

        $call = fn (string $signature) => $this->call('POST', route('payment.webhook', 'stripe'), [], [], [], [
            'HTTP_STRIPE_SIGNATURE' => $signature, 'CONTENT_TYPE' => 'application/json',
        ], $payload);

        $call('t='.time().',v1=bad')->assertStatus(400);
        $this->assertFalse($order->fresh()->isPaid());

        $t = time();
        $call("t={$t},v1=".hash_hmac('sha256', $t.'.'.$payload, 'whsec_test'))->assertNoContent();
        $this->assertTrue($order->fresh()->isPaid());
        $this->assertSame('pi_9', $order->fresh()->payment_reference);
    }

    public function test_paypal_creates_the_order_and_captures_on_return(): void
    {
        config(['services.paypal.client_id' => 'id', 'services.paypal.secret' => 'secret']);
        Http::fake([
            '*/v1/oauth2/token' => Http::response(['access_token' => 'tok']),
            '*/v2/checkout/orders' => Http::response(['id' => 'PP1', 'links' => [['rel' => 'approve', 'href' => 'https://paypal.test/approve']]]),
            '*/v2/checkout/orders/PP1/capture' => Http::response(['status' => 'COMPLETED', 'purchase_units' => [['payments' => ['captures' => [['id' => 'CAP1']]]]]]),
        ]);

        $this->checkoutWith('paypal')->assertRedirect('https://paypal.test/approve');

        $order = Order::firstOrFail();
        $this->assertSame('PP1', $order->payment_reference);

        $this->get(route('payment.return', [$order, 'gateway' => 'paypal', 'token' => 'PP1']))->assertSessionHas('success');

        $this->assertTrue($order->fresh()->isPaid());
        $this->assertSame('CAP1', $order->fresh()->payment_reference);
    }

    public function test_paypal_return_with_a_foreign_token_does_not_capture(): void
    {
        config(['services.paypal.client_id' => 'id', 'services.paypal.secret' => 'secret']);
        Http::fake();
        $order = $this->order('paypal', ['payment_reference' => 'PP1']);

        $this->withSession(['my_orders' => [$order->number]])
            ->get(route('payment.return', [$order, 'gateway' => 'paypal', 'token' => 'OTHER']));

        Http::assertNothingSent();
        $this->assertFalse($order->fresh()->isPaid());
    }

    public function test_mercado_pago_creates_a_preference_and_confirms_through_the_webhook(): void
    {
        config(['services.mercadopago.access_token' => 'APP_USR-x']);
        Http::fake(['api.mercadopago.com/checkout/preferences' => Http::response(['id' => 'pref_1', 'init_point' => 'https://mp.test/pay'])]);

        $this->checkoutWith('mercadopago')->assertRedirect('https://mp.test/pay');

        $order = Order::firstOrFail();
        Http::fake(['api.mercadopago.com/v1/payments/555' => Http::response([
            'status' => 'approved', 'external_reference' => $order->number, 'transaction_amount' => $order->total / 100,
        ])]);

        $this->postJson(route('payment.webhook', 'mercadopago'), ['type' => 'payment', 'data' => ['id' => '555']])->assertNoContent();

        $this->assertTrue($order->fresh()->isPaid());
        $this->assertSame('555', $order->fresh()->payment_reference);
    }

    public function test_mercado_pago_ignores_payments_that_do_not_cover_the_total(): void
    {
        config(['services.mercadopago.access_token' => 'APP_USR-x']);
        $order = $this->order('mercadopago');
        Http::fake(['api.mercadopago.com/v1/payments/556' => Http::response([
            'status' => 'approved', 'external_reference' => $order->number, 'transaction_amount' => 1.00,
        ])]);

        $this->postJson(route('payment.webhook', 'mercadopago'), ['type' => 'payment', 'data' => ['id' => '556']]);

        $this->assertFalse($order->fresh()->isPaid());
    }

    public function test_webhook_for_a_disabled_gateway_is_not_found(): void
    {
        $this->postJson(route('payment.webhook', 'stripe'), [])->assertNotFound();
    }

    public function test_a_gateway_failure_keeps_the_order_and_shows_a_message(): void
    {
        config(['services.stripe.secret' => 'sk_test_x']);
        Http::fake(['api.stripe.com/*' => Http::response(['error' => ['message' => 'boom']], 500)]);

        $this->checkoutWith('stripe')->assertRedirect(route('orders.show', Order::firstOrFail()))->assertSessionHas('error');

        $this->assertSame(1, Order::count());
    }

    public function test_mark_paid_is_idempotent(): void
    {
        $order = $this->order('stripe');

        $this->assertTrue($order->markPaid('a'));
        $this->assertFalse($order->markPaid('b'));
        $this->assertSame('a', $order->fresh()->payment_reference);
    }
}
