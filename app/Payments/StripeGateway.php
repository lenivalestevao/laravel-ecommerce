<?php

namespace App\Payments;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Stripe Checkout (hosted page) through the REST API.
 * Docs: https://docs.stripe.com/api/checkout/sessions
 */
class StripeGateway implements Gateway
{
    private const API = 'https://api.stripe.com/v1';

    public function key(): string
    {
        return 'stripe';
    }

    public function label(): string
    {
        return 'Credit card (Stripe)';
    }

    public function enabled(): bool
    {
        return filled(config('services.stripe.secret'));
    }

    public function createPayment(Order $order): string
    {
        $order->loadMissing('items');

        $data = [
            'mode' => 'payment',
            'client_reference_id' => $order->number,
            'customer_email' => $order->customer_email,
            'success_url' => route('payment.return', [$order, 'gateway' => 'stripe']).'&session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => route('payment.cancel', $order),
            'metadata' => ['order' => $order->number],
        ];

        $i = 0;
        foreach ($order->items as $item) {
            $data["line_items[$i][quantity]"] = $item->quantity;
            $data["line_items[$i][price_data][currency]"] = config('services.stripe.currency');
            $data["line_items[$i][price_data][unit_amount]"] = $item->price;
            $data["line_items[$i][price_data][product_data][name]"] = $item->name;
            $i++;
        }

        // Shipping, tax and discount are sent as single adjusted lines so the Stripe total matches the order.
        $extra = $order->shipping + $order->tax - $order->discount;
        if ($extra !== 0) {
            $data["line_items[$i][quantity]"] = 1;
            $data["line_items[$i][price_data][currency]"] = config('services.stripe.currency');
            $data["line_items[$i][price_data][unit_amount]"] = max($extra, 0);
            $data["line_items[$i][price_data][product_data][name]"] = 'Shipping, tax and discounts';
        }

        $response = Http::withToken(config('services.stripe.secret'))
            ->asForm()
            ->post(self::API.'/checkout/sessions', $data);

        if ($response->failed() || ! $response->json('url')) {
            throw new RuntimeException('Stripe could not create the payment: '.$response->json('error.message', 'unknown error'));
        }

        $order->update(['payment_reference' => $response->json('id')]);

        return $response->json('url');
    }

    public function confirm(Order $order, Request $request): bool
    {
        $sessionId = $request->query('session_id', $order->payment_reference);

        if (! $sessionId) {
            return false;
        }

        $response = Http::withToken(config('services.stripe.secret'))
            ->get(self::API.'/checkout/sessions/'.$sessionId);

        if ($response->successful()
            && $response->json('payment_status') === 'paid'
            && $response->json('client_reference_id') === $order->number) {
            $order->markPaid($response->json('payment_intent') ?? $sessionId);
        }

        return $order->fresh()->isPaid();
    }

    public function webhook(Request $request): void
    {
        $payload = $request->getContent();

        if (! $this->validSignature($payload, (string) $request->header('Stripe-Signature'))) {
            abort(400, 'Invalid signature');
        }

        $event = json_decode($payload, true);

        if (($event['type'] ?? null) === 'checkout.session.completed') {
            $session = $event['data']['object'] ?? [];

            if (($session['payment_status'] ?? null) === 'paid') {
                Order::where('number', $session['client_reference_id'] ?? '')
                    ->first()
                    ?->markPaid($session['payment_intent'] ?? $session['id'] ?? null);
            }
        }
    }

    /** Verifies the Stripe-Signature header (HMAC SHA-256 over "timestamp.payload"). */
    private function validSignature(string $payload, string $header): bool
    {
        $secret = config('services.stripe.webhook_secret');

        if (blank($secret) || $header === '') {
            return false;
        }

        $timestamp = null;
        $signatures = [];

        foreach (explode(',', $header) as $part) {
            [$k, $v] = array_pad(explode('=', $part, 2), 2, null);
            if ($k === 't') {
                $timestamp = $v;
            } elseif ($k === 'v1') {
                $signatures[] = $v;
            }
        }

        if ($timestamp === null || abs(time() - (int) $timestamp) > 300) {
            return false;
        }

        $expected = hash_hmac('sha256', $timestamp.'.'.$payload, $secret);

        foreach ($signatures as $signature) {
            if (hash_equals($expected, (string) $signature)) {
                return true;
            }
        }

        return false;
    }
}
