<x-shop title="My account">
    <h1>My account</h1>
    <div class="two-col">
        <section class="card pad">
            <h2>My details</h2>
            <form method="post" action="{{ route('account.update') }}">
                @csrf @method('PUT')
                <x-field name="name" label="Full name" :value="$user->name" required />
                <x-field name="email" label="Email" type="email" :value="$user->email" required />
                <x-field name="phone" label="Phone" :value="$user->phone" />
                <x-field name="password" label="New password" type="password" hint="Leave blank to keep the current one" />
                <x-field name="password_confirmation" label="Confirm new password" type="password" />
                <button class="btn" type="submit">Save</button>
            </form>
        </section>

        <section class="card pad">
            <h2>My orders</h2>
            @forelse ($orders as $order)
                <p>
                    <a href="{{ route('orders.show', $order) }}"><strong>{{ $order->number }}</strong></a>
                    <span class="pill pill-{{ $order->status }}">{{ $order->statusLabel() }}</span><br>
                    <small class="muted">{{ $order->created_at->format('M j, Y') }} &middot; {{ money($order->total) }}</small>
                </p>
            @empty
                <p class="muted">You have not placed any orders yet.</p>
            @endforelse
            {{ $orders->links() }}
        </section>
    </div>
</x-shop>
