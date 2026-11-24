<x-shop title="Track an order">
    <h1>Track an order</h1>
    <form method="post" action="{{ route('track.lookup') }}" class="card pad narrow">
        @csrf
        <x-field name="number" label="Order number" hint="For example LS-2026-000001" required />
        <x-field name="email" label="Email used at checkout" type="email" required />
        <button class="btn" type="submit">Find my order</button>
    </form>
</x-shop>
