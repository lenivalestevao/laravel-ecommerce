<?php

namespace App\Payments;

use App\Models\Order;
use Illuminate\Http\Request;

interface Gateway
{
    /** Key stored in orders.payment_method, e.g. "stripe". */
    public function key(): string;

    public function label(): string;

    /** True when the API keys for this gateway are configured. */
    public function enabled(): bool;

    /** Creates a hosted payment page and returns the URL to send the customer to. */
    public function createPayment(Order $order): string;

    /** Called when the customer returns from the gateway. Returns true when the order is paid. */
    public function confirm(Order $order, Request $request): bool;

    /** Handles the gateway server-to-server notification. */
    public function webhook(Request $request): void;
}
