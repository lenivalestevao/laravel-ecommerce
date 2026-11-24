<?php

namespace App\Payments;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Mercado Pago Checkout Pro (preferences API).
 * Docs: https://www.mercadopago.com.br/developers/en/reference/preferences/_checkout_preferences/post
 */
class MercadoPagoGateway implements Gateway
{
    private const API = 'https://api.mercadopago.com';

    public function key(): string
    {
        return 'mercadopago';
    }

    public function label(): string
    {
        return 'Mercado Pago';
    }

    public function enabled(): bool
    {
        return filled(config('services.mercadopago.access_token'));
    }

    private function http()
    {
        return Http::withToken(config('services.mercadopago.access_token'));
    }

    public function createPayment(Order $order): string
    {
        $order->loadMissing('items');

        $response = $this->http()->post(self::API.'/checkout/preferences', [
            'items' => [[
                'title' => 'Order '.$order->number,
                'quantity' => 1,
                'currency_id' => config('services.mercadopago.currency'),
                'unit_price' => round($order->total / 100, 2),
            ]],
            'payer' => ['email' => $order->customer_email],
            'external_reference' => $order->number,
            'back_urls' => [
                'success' => route('payment.return', [$order, 'gateway' => 'mercadopago']),
                'pending' => route('payment.return', [$order, 'gateway' => 'mercadopago']),
                'failure' => route('payment.cancel', $order),
            ],
            'auto_return' => 'approved',
            'notification_url' => route('payment.webhook', 'mercadopago'),
        ]);

        if ($response->failed() || ! $response->json('init_point')) {
            throw new RuntimeException('Mercado Pago could not create the payment.');
        }

        $order->update(['payment_reference' => $response->json('id')]);

        return $response->json('init_point');
    }

    public function confirm(Order $order, Request $request): bool
    {
        // Never trust the query string alone: ask the API for the payment and check it.
        $paymentId = $request->query('payment_id');

        if ($paymentId) {
            $this->syncPayment((string) $paymentId, $order);
        }

        return $order->fresh()->isPaid();
    }

    public function webhook(Request $request): void
    {
        $id = $request->input('data.id') ?? $request->query('data_id') ?? $request->query('id');
        $type = $request->input('type') ?? $request->query('topic');

        if ($id && in_array($type, ['payment', null], true)) {
            $this->syncPayment((string) $id);
        }
    }

    /** Fetches the payment from Mercado Pago and marks the matching order as paid when approved. */
    private function syncPayment(string $paymentId, ?Order $expected = null): void
    {
        $response = $this->http()->get(self::API.'/v1/payments/'.$paymentId);

        if ($response->failed() || $response->json('status') !== 'approved') {
            return;
        }

        $order = Order::where('number', $response->json('external_reference'))->first();

        if ($order === null || ($expected && $expected->isNot($order))) {
            return;
        }

        // The approved amount must cover the order total.
        if ((int) round(((float) $response->json('transaction_amount')) * 100) >= $order->total) {
            $order->markPaid($paymentId);
        }
    }
}
