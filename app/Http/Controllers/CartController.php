<?php

namespace App\Http\Controllers;

use App\Models\Coupon;
use App\Models\Product;
use App\Support\Cart;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function index(Cart $cart)
    {
        return view('cart.index', [
            'lines' => $cart->lines(),
            'totals' => $cart->totals(),
            'coupon' => $cart->coupon(),
        ]);
    }

    public function add(Request $request, Cart $cart, Product $product)
    {
        abort_unless($product->is_active, 404);

        $quantity = $request->validate(['quantity' => ['nullable', 'integer', 'min:1', 'max:99']])['quantity'] ?? 1;

        if (! $product->inStock()) {
            return back()->with('error', "\"{$product->name}\" is out of stock.");
        }

        $cart->add($product, $quantity);

        return back()->with('success', "\"{$product->name}\" was added to your cart.");
    }

    public function update(Request $request, Cart $cart, Product $product)
    {
        $data = $request->validate(['quantity' => ['required', 'integer', 'min:0', 'max:99']]);

        $cart->set($product, $data['quantity']);

        return back();
    }

    public function remove(Cart $cart, Product $product)
    {
        $cart->remove($product);

        return back()->with('success', 'Item removed.');
    }

    public function applyCoupon(Request $request, Cart $cart)
    {
        $data = $request->validate(['code' => ['required', 'string', 'max:40']]);

        $coupon = Coupon::where('code', strtoupper(trim($data['code'])))->first();

        if ($coupon === null) {
            return back()->withErrors(['code' => 'This coupon code does not exist.']);
        }

        if ($problem = $coupon->problemFor($cart->subtotal())) {
            return back()->withErrors(['code' => $problem]);
        }

        $cart->applyCoupon($coupon);

        return back()->with('success', "Coupon {$coupon->code} applied.");
    }

    public function removeCoupon(Cart $cart)
    {
        $cart->removeCoupon();

        return back();
    }
}
