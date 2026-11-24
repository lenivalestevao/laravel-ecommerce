<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Payments\PaymentManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Throwable;

class PaymentController extends Controller
{
    /** The customer comes back from Stripe, PayPal or Mercado Pago. */
    public function return(Request $request, Order $order, PaymentManager $payments)
    {
        Gate::authorize('view-order', $order);

        $gateway = $payments->gateway($order->payment_method);

        if ($gateway && $request->query('gateway') === $gateway->key()) {
            try {
                $paid = $gateway->confirm($order, $request);
            } catch (Throwable $e) {
                report($e);
                $paid = false;
            }

            return redirect()->route('orders.show', $order)->with(
                $paid ? 'success' : 'error',
                $paid ? 'Payment received. Thank you!' : 'We have not received the payment yet. If you paid, it will be confirmed shortly.'
            );
        }

        return redirect()->route('orders.show', $order);
    }

    public function cancel(Order $order)
    {
        Gate::authorize('view-order', $order);

        return redirect()->route('orders.show', $order)->with('error', 'The payment was cancelled. You can try again below.');
    }

    /** Server-to-server notification. Each gateway verifies it on its own. */
    public function webhook(Request $request, PaymentManager $payments, string $gateway)
    {
        $handler = $payments->gateway($gateway);

        abort_if($handler === null, 404);

        $handler->webhook($request);

        return response()->noContent();
    }
}
