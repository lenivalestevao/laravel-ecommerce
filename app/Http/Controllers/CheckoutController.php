<?php

namespace App\Http\Controllers;

use App\Http\Requests\CheckoutRequest;
use App\Mail\NewOrderAlert;
use App\Mail\OrderPlaced;
use App\Models\Order;
use App\Payments\PaymentManager;
use App\Services\CheckoutService;
use App\Support\Cart;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Mail;
use Throwable;

class CheckoutController extends Controller
{
    public function create(Cart $cart, PaymentManager $payments)
    {
        if ($cart->isEmpty()) {
            return redirect()->route('cart.index');
        }

        return view('checkout.create', [
            'lines' => $cart->lines(),
            'totals' => $cart->totals(),
            'methods' => $payments->methods(),
        ]);
    }

    public function store(CheckoutRequest $request, Cart $cart, CheckoutService $checkout, PaymentManager $payments)
    {
        $order = $checkout->placeOrder($cart, $request->validated(), $request->user());

        $cart->clear();
        $request->session()->push('my_orders', $order->number);

        $this->notify($order);

        if ($gateway = $payments->gateway($order->payment_method)) {
            try {
                return redirect()->away($gateway->createPayment($order));
            } catch (Throwable $e) {
                report($e);

                return redirect()->route('orders.show', $order)
                    ->with('error', 'We could not reach the payment provider. Your order was saved, you can try paying again.');
            }
        }

        return redirect()->route('orders.show', $order)->with('success', 'Thank you! Your order was placed.');
    }

    /** Order confirmation page, also used to retry an online payment. */
    public function show(Order $order)
    {
        Gate::authorize('view-order', $order);

        return view('checkout.show', ['order' => $order->load('items')]);
    }

    public function pay(Order $order, PaymentManager $payments)
    {
        Gate::authorize('view-order', $order);

        $gateway = $payments->gateway($order->payment_method);

        abort_if($gateway === null || $order->isPaid() || $order->status === 'cancelled', 404);

        try {
            return redirect()->away($gateway->createPayment($order));
        } catch (Throwable $e) {
            report($e);

            return back()->with('error', 'The payment provider is not available right now. Please try again later.');
        }
    }

    private function notify(Order $order): void
    {
        try {
            Mail::to($order->customer_email)->send(new OrderPlaced($order->load('items')));
            Mail::to(config('shop.contact_email'))->send(new NewOrderAlert($order));
        } catch (Throwable $e) {
            // A mail problem must never lose a sale.
            report($e);
        }
    }
}
