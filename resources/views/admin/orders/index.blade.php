<x-admin title="Orders">
    <div class="toolbar">
        <form method="get" class="search">
            <input type="search" name="q" value="{{ $term }}" placeholder="Number, name or email">
            <select name="status" aria-label="Status">
                <option value="">All statuses</option>
                @foreach (config('shop.order_statuses') as $key => $label)
                    <option value="{{ $key }}" @selected($status === $key)>{{ $label }}</option>
                @endforeach
            </select>
            <button class="btn">Filter</button>
        </form>
        <a class="btn" href="{{ route('admin.orders.export', request()->query()) }}">Export CSV</a>
    </div>
    <div class="card">
        <table class="table">
            <thead><tr><th>Order</th><th>Date</th><th>Customer</th><th>Status</th><th>Payment</th><th class="num">Total</th></tr></thead>
            <tbody>
            @forelse ($orders as $order)
                <tr>
                    <td><a href="{{ route('admin.orders.show', $order) }}">{{ $order->number }}</a></td>
                    <td>{{ $order->created_at->format('M j, Y H:i') }}</td>
                    <td>{{ $order->customer_name }}</td>
                    <td><span class="pill pill-{{ $order->status }}">{{ $order->statusLabel() }}</span></td>
                    <td>{{ $order->paymentLabel() }} <span class="pill {{ $order->isPaid() ? 'pill-completed' : 'pill-pending' }}">{{ $order->isPaid() ? 'Paid' : 'Unpaid' }}</span></td>
                    <td class="num">{{ money($order->total) }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="muted">No orders found.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    {{ $orders->links() }}
</x-admin>
