<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\CheckoutService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OrderController extends Controller
{
    private function filtered(Request $request)
    {
        return Order::query()
            ->when($request->query('status'), fn ($q, $s) => $q->where('status', $s))
            ->when($request->query('q'), fn ($q, $term) => $q->where(function ($q) use ($term) {
                $q->where('number', 'like', "%{$term}%")
                    ->orWhere('customer_name', 'like', "%{$term}%")
                    ->orWhere('customer_email', 'like', "%{$term}%");
            }))
            ->latest();
    }

    public function index(Request $request)
    {
        return view('admin.orders.index', [
            'orders' => $this->filtered($request)->paginate(20)->withQueryString(),
            'status' => $request->query('status'),
            'term' => $request->query('q'),
        ]);
    }

    public function show(Order $order)
    {
        return view('admin.orders.show', ['order' => $order->load('items', 'user', 'coupon')]);
    }

    public function update(Request $request, Order $order, CheckoutService $checkout)
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(array_keys(config('shop.order_statuses')))],
        ]);

        $wasCancelled = $order->status === 'cancelled';

        $order->update($data);

        // Cancelling gives the stock back once.
        if (! $wasCancelled && $data['status'] === 'cancelled') {
            $checkout->restock($order);
        }

        return back()->with('success', 'Order status updated.');
    }

    public function markPaid(Order $order)
    {
        $order->markPaid();

        return back()->with('success', 'Order marked as paid.');
    }

    /** Streams all orders matching the current filters as a CSV file. */
    public function export(Request $request): StreamedResponse
    {
        $query = $this->filtered($request);

        return response()->streamDownload(function () use ($query) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Number', 'Date', 'Customer', 'Email', 'Status', 'Payment', 'Paid', 'Subtotal', 'Discount', 'Shipping', 'Tax', 'Total']);

            $query->chunk(200, function ($orders) use ($out) {
                foreach ($orders as $o) {
                    fputcsv($out, [
                        $o->number, $o->created_at->toDateTimeString(), $o->customer_name, $o->customer_email,
                        $o->status, $o->payment_method, $o->payment_status,
                        $o->subtotal / 100, $o->discount / 100, $o->shipping / 100, $o->tax / 100, $o->total / 100,
                    ]);
                }
            });

            fclose($out);
        }, 'orders-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv']);
    }
}
