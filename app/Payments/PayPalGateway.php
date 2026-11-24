<?php

namespace App\Payments;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * PayPal Checkout through the Orders v2 REST API (redirect flow).
 * Docs: https://developer.paypal.com/docs/api/orders/v2/
 */
class PayPalGateway implements Gateway
{
    public function key(): string
    {
        return 'paypal';
    }

    public function label(): string
    {
        return 'PayPal';
    }

    public function enabled(): bool
    {
        return filled(config('services.paypal.client_id')) && filled(config('services.paypal.secret'));
    }

    private function base(): string
    {
        return config('services.paypal.sandbox')
            ? 'https://api-m.sandbox.paypal.com'
            : 'https://api-m.paypal.com';
    }

    private function token(): string
    {
        $response = Http::asForm()
            ->withBasicAuth(config('services.paypal.client_id'), config('services.paypal.secret'))
            ->post($this->base().'/v1/oauth2/token', ['grant_type' => 'client_credentials']);

        if ($response->failed()) {
            throw new RuntimeException('PayPal authentication failed.');
        }

        return $response->json('access_token');
    }

    public function createPayment(Order $order): string
    {
        $response = Http::withToken($this->token())->post($this->base().'/v2/checkout/orders', [
            'intent' => 'CAPTURE',
            'purchase_units' => [[
                'reference_id' => $order->number,
                'invoice_id' => $order->number,
                'amount' => [
                    'currency_code' => config('services.paypal.currency'),
                    'value' => number_format($order->total / 100, 2, '.', ''),
                ],
            ]],
            'application_context' => [
                'return_url' => route('payment.return', [$order, 'gateway' => 'paypal']),
                'cancel_url' => route('payment.cancel', $order),
                'user_action' => 'PAY_NOW',
            ],
        ]);

        $approve = collect($response->json('links', []))->firstWhere('rel', 'approve')['href']
            ?? collect($response->json('links', []))->firstWhere('rel', 'payer-action')['href']
            ?? null;

        if ($response->failed() || ! $approve) {
            throw new RuntimeException('PayPal could not create the payment.');
        }

        $order->update(['payment_reference' => $response->json('id')]);

        return $approve;
    }

    public function confirm(Order $order, Request $request): bool
    {
        // PayPal returns ?token=<paypal order id>. It must match the order we created.
        $paypalOrderId = $request->query('token');

        if (! $paypalOrderId || $paypalOrderId !== $order->payment_reference) {
            return $order->isPaid();
        }

        $response = Http::withToken($this->token())
            ->withBody('{}', 'application/json')
            ->post($this->base()."/v2/checkout/orders/{$paypalOrderId}/capture");

        if ($response->json('status') === 'COMPLETED') {
            $capture = $response->json('purchase_units.0.payments.captures.0.id');
            $order->markPaid($capture ?? $paypalOrderId);
        }

        return $order->fresh()->isPaid();
    }

    public function webhook(Request $request): void
    {
        // The customer return URL captures the payment. For production, verify the webhook
        // signature with PayPal's /v1/notifications/verify-webhook-signature endpoint
        // before trusting events here.
    }
}
