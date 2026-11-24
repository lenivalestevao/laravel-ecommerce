<x-admin :title="'Order '.$order->number">
    <div class="two-col">
        <section class="card pad">
            <h2>Status</h2>
            <form method="post" action="{{ route('admin.orders.update', $order) }}" class="inline-form">
                @csrf @method('PATCH')
                <select name="status" aria-label="Order status">
                    @foreach (config('shop.order_statuses') as $key => $label)
                        <option value="{{ $key }}" @selected($order->status === $key)>{{ $label }}</option>
                    @endforeach
                </select>
                <button class="btn" type="submit">Update</button>
            </form>

            <p>Payment: {{ $order->paymentLabel() }}
                <span class="pill {{ $order->isPaid() ? 'pill-completed' : 'pill-pending' }}">{{ $order->isPaid() ? 'Paid' : 'Unpaid' }}</span>
                @if ($order->payment_reference)<br><small class="muted">Reference {{ $order->payment_reference }}</small>@endif</p>
            @unless ($order->isPaid())
                <form method="post" action="{{ route('admin.orders.paid', $order) }}">@csrf<button class="btn" type="submit">Mark as paid</button></form>
            @endunless
        </section>

        <section class="card pad">
            <h2>Customer</h2>
            <p>{{ $order->customer_name }}<br>{{ $order->customer_email }}<br>{{ $order->customer_phone }}</p>
            <p>{{ $order->address }}<br>{{ $order->city }}@if ($order->state), {{ $order->state }}@endif {{ $order->postcode }}<br>{{ $order->country }}</p>
            @if ($order->notes)<p><em>{{ $order->notes }}</em></p>@endif
        </section>
    </div>

    <section class="card pad">
        <h2>Items</h2>
        <table class="table">
            <thead><tr><th>Product</th><th>SKU</th><th>Price</th><th>Qty</th><th class="num">Total</th></tr></thead>
            <tbody>
            @foreach ($order->items as $item)
                <tr><td>{{ $item->name }}</td><td>{{ $item->sku }}</td><td>{{ money($item->price) }}</td><td>{{ $item->quantity }}</td><td class="num">{{ money($item->total) }}</td></tr>
            @endforeach
            </tbody>
        </table>
        <x-totals :totals="(object) $order->only(['subtotal', 'discount', 'shipping', 'tax', 'total'])" />
        @if ($order->coupon)<p class="muted">Coupon used: {{ $order->coupon->code }}</p>@endif
    </section>
</x-admin>
