<x-shop :title="'Order '.$order->number">
    <h1>Order {{ $order->number }}</h1>
    <p>Status: <span class="pill pill-{{ $order->status }}">{{ $order->statusLabel() }}</span>
       &middot; Payment: {{ $order->paymentLabel() }}
       <span class="pill {{ $order->isPaid() ? 'pill-completed' : 'pill-pending' }}">{{ $order->isPaid() ? 'Paid' : 'Not paid' }}</span></p>

    @if (! $order->isPaid() && $order->status !== 'cancelled')
        @if (app(\App\Payments\PaymentManager::class)->isOnline($order->payment_method))
            <form method="post" action="{{ route('orders.pay', $order) }}">
                @csrf
                <button class="btn btn-lg" type="submit">Pay now with {{ $order->paymentLabel() }}</button>
            </form>
        @elseif ($order->payment_method === 'bank')
            <div class="card pad">
                <strong>Bank transfer</strong>
                <p>Please transfer {{ money($order->total) }} using <strong>{{ $order->number }}</strong> as the reference. Replace this text with your bank details in <code>resources/views/checkout/show.blade.php</code>.</p>
            </div>
        @endif
    @endif

    <div class="two-col">
        <section class="card pad">
            <h2>Items</h2>
            <table class="table">
                <thead><tr><th>Product</th><th>Price</th><th>Qty</th><th class="num">Total</th></tr></thead>
                <tbody>
                @foreach ($order->items as $item)
                    <tr><td>{{ $item->name }}<br><small class="muted">{{ $item->sku }}</small></td><td>{{ money($item->price) }}</td><td>{{ $item->quantity }}</td><td class="num">{{ money($item->total) }}</td></tr>
                @endforeach
                </tbody>
            </table>
            <x-totals :totals="(object) $order->only(['subtotal', 'discount', 'shipping', 'tax', 'total'])" />
        </section>

        <section class="card pad">
            <h2>Delivery</h2>
            <p>{{ $order->customer_name }}<br>{{ $order->address }}<br>{{ $order->city }}@if ($order->state), {{ $order->state }}@endif {{ $order->postcode }}<br>{{ $order->country }}</p>
            <p class="muted">{{ $order->customer_email }}@if ($order->customer_phone) &middot; {{ $order->customer_phone }}@endif</p>
            @if ($order->notes)<p><em>{{ $order->notes }}</em></p>@endif
        </section>
    </div>
</x-shop>
