<?php

namespace App\Payments;

/**
 * Knows every gateway and which ones are switched on.
 * Offline methods (cash on delivery, bank transfer) are always available.
 */
class PaymentManager
{
    /** @var array<string, Gateway> */
    private array $gateways;

    public function __construct(StripeGateway $stripe, PayPalGateway $paypal, MercadoPagoGateway $mercadopago)
    {
        $this->gateways = [
            $stripe->key() => $stripe,
            $paypal->key() => $paypal,
            $mercadopago->key() => $mercadopago,
        ];
    }

    public function gateway(string $key): ?Gateway
    {
        $gateway = $this->gateways[$key] ?? null;

        return $gateway?->enabled() ? $gateway : null;
    }

    /** @return array<string, string> key => label for every method the customer can pick. */
    public function methods(): array
    {
        $methods = [];

        foreach ($this->gateways as $key => $gateway) {
            if ($gateway->enabled()) {
                $methods[$key] = $gateway->label();
            }
        }

        return $methods + config('shop.payment_methods');
    }

    public function isOnline(string $key): bool
    {
        return $this->gateway($key) !== null;
    }
}
