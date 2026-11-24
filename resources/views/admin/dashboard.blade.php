<x-admin title="Dashboard">
    <div class="stats">
        <div class="card stat"><small class="muted">Paid revenue</small><strong>{{ money($revenue) }}</strong></div>
        <div class="card stat"><small class="muted">Orders</small><strong>{{ $ordersCount }}</strong></div>
        <div class="card stat"><small class="muted">Pending</small><strong>{{ $pendingCount }}</strong></div>
        <div class="card stat"><small class="muted">Customers</small><strong>{{ $customersCount }}</strong></div>
    </div>

    <div class="two-col">
        <section class="card pad">
            <h2>Recent orders</h2>
            <table class="table">
                <tbody>
                @forelse ($recent as $order)
                    <tr>
                        <td><a href="{{ route('admin.orders.show', $order) }}">{{ $order->number }}</a></td>
                        <td>{{ $order->customer_name }}</td>
                        <td><span class="pill pill-{{ $order->status }}">{{ $order->statusLabel() }}</span></td>
                        <td class="num">{{ money($order->total) }}</td>
                    </tr>
                @empty
                    <tr><td class="muted">No orders yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </section>

        <section class="card pad">
            <h2>Low stock</h2>
            <table class="table">
                <tbody>
                @forelse ($lowStock as $product)
                    <tr><td><a href="{{ route('admin.products.edit', $product) }}">{{ $product->name }}</a></td><td class="num">{{ $product->stock }} left</td></tr>
                @empty
                    <tr><td class="muted">All products are well stocked.</td></tr>
                @endforelse
                </tbody>
            </table>
        </section>
    </div>
</x-admin>
