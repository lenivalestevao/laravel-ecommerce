<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Support\Cart;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CheckoutService
{
    /**
     * Turns the cart into an order inside a transaction, locking products so stock
     * cannot go negative when two customers buy the last unit at the same time.
     *
     * @param  array<string, mixed>  $data  validated checkout form data
     */
    public function placeOrder(Cart $cart, array $data, ?User $user = null): Order
    {
        return DB::transaction(function () use ($cart, $data, $user) {
            $lines = $cart->lines();

            if ($lines->isEmpty()) {
                throw ValidationException::withMessages(['cart' => 'Your cart is empty.']);
            }

            $locked = Product::whereKey($lines->pluck('product.id'))->lockForUpdate()->get()->keyBy('id');

            foreach ($lines as $line) {
                $product = $locked[$line->product->id];

                if ($product->stock < $line->quantity) {
                    throw ValidationException::withMessages([
                        'cart' => "Sorry, only {$product->stock} of \"{$product->name}\" left in stock.",
                    ]);
                }
            }

            $coupon = $cart->coupon();
            $totals = $cart->totals();

            $order = Order::create([
                'number' => Order::nextNumber(),
                'user_id' => $user?->id,
                'coupon_id' => $coupon?->id,
                'status' => 'pending',
                'payment_method' => $data['payment_method'],
                'payment_status' => 'unpaid',
                'customer_name' => $data['customer_name'],
                'customer_email' => $data['customer_email'],
                'customer_phone' => $data['customer_phone'] ?? null,
                'address' => $data['address'],
                'city' => $data['city'],
                'state' => $data['state'] ?? null,
                'postcode' => $data['postcode'],
                'country' => $data['country'],
                'notes' => $data['notes'] ?? null,
                'subtotal' => $totals->subtotal,
                'discount' => $totals->discount,
                'shipping' => $totals->shipping,
                'tax' => $totals->tax,
                'total' => $totals->total,
            ]);

            foreach ($lines as $line) {
                $product = $locked[$line->product->id];

                $order->items()->create([
                    'product_id' => $product->id,
                    'name' => $product->name,
                    'sku' => $product->sku,
                    'price' => $product->current_price,
                    'quantity' => $line->quantity,
                    'total' => $product->current_price * $line->quantity,
                ]);

                $product->decrement('stock', $line->quantity);
            }

            $coupon?->increment('used_count');

            return $order;
        });
    }

    /** Puts stock and the coupon use back when an order is cancelled. */
    public function restock(Order $order): void
    {
        DB::transaction(function () use ($order) {
            foreach ($order->items()->with('product')->get() as $item) {
                $item->product?->increment('stock', $item->quantity);
            }

            $order->coupon?->decrement('used_count');
        });
    }
}
