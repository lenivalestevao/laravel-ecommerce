<x-shop title="Home">
    <section class="hero">
        <h1>{{ config('shop.name') }}</h1>
        <p>{{ config('shop.tagline') }}</p>
        <a class="btn btn-lg" href="{{ route('shop.index') }}">Shop now</a>
    </section>

    <section class="category-strip" aria-label="Categories">
        @foreach ($categories as $c)
            <a class="card cat-tile" href="{{ route('shop.category', $c) }}">
                <strong>{{ $c->name }}</strong>
                <small class="muted">{{ $c->products_count }} {{ str('product')->plural($c->products_count) }}</small>
            </a>
        @endforeach
    </section>

    @if ($featured->isNotEmpty())
        <h2>Featured products</h2>
        <div class="grid">
            @foreach ($featured as $product)<x-product-card :product="$product" />@endforeach
        </div>
    @endif

    <h2>New arrivals</h2>
    <div class="grid">
        @forelse ($latest as $product)
            <x-product-card :product="$product" />
        @empty
            <p class="muted">No products yet. Add some from the admin panel.</p>
        @endforelse
    </div>
</x-shop>
