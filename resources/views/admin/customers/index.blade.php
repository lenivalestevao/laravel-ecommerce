<x-admin title="Customers">
    <div class="toolbar">
        <form method="get" class="search"><input type="search" name="q" value="{{ $term }}" placeholder="Name or email"><button class="btn">Search</button></form>
    </div>
    <div class="card">
        <table class="table">
            <thead><tr><th>Name</th><th>Email</th><th>Phone</th><th>Orders</th><th class="num">Spent</th><th>Joined</th></tr></thead>
            <tbody>
            @forelse ($customers as $c)
                <tr>
                    <td>{{ $c->name }}</td><td>{{ $c->email }}</td><td>{{ $c->phone }}</td>
                    <td>{{ $c->orders_count }}</td><td class="num">{{ money((int) $c->orders_sum_total) }}</td>
                    <td>{{ $c->created_at->format('M j, Y') }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="muted">No customers yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    {{ $customers->links() }}
</x-admin>
