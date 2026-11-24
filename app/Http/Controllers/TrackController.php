<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;

class TrackController extends Controller
{
    public function form()
    {
        return view('track');
    }

    public function lookup(Request $request)
    {
        $data = $request->validate([
            'number' => ['required', 'string', 'max:40'],
            'email' => ['required', 'email'],
        ]);

        $order = Order::where('number', strtoupper(trim($data['number'])))
            ->where('customer_email', $data['email'])
            ->first();

        if ($order === null) {
            return back()->withInput()->withErrors(['number' => 'We could not find an order with these details.']);
        }

        // Proving the number and email unlocks the order page for this session.
        $request->session()->push('my_orders', $order->number);

        return redirect()->route('orders.show', $order);
    }
}
