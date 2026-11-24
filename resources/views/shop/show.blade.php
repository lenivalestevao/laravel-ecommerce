<x-shop :title="$product->name" :description="$product->short_description">
    <p class="crumbs">
        <a href="{{ route('home') }}">Home</a> /
        @if ($product->category)<a href="{{ route('shop.category', $product->category) }}">{{ $product->category->name }}</a> /@endif
        {{ $product->name }}
    </p>

    <div class="product-detail">
        <div class="card detail-image">
            @if ($product->image_url)
                <img src="{{ $product->image_url }}" alt="{{ $product->name }}">
            @else
                <span class="placeholder big" aria-hidden="true">{{ mb_strtoupper(mb_substr($product->name, 0, 1)) }}</span>
            @endif
        </div>

        <div>
            <h1>{{ $product->name }}</h1>
            <p class="muted">SKU {{ $product->sku }}</p>
            <p class="price big">
                @if ($product->onSale())<s>{{ money($product->price) }}</s> @endif
                <strong>{{ money($product->current_price) }}</strong>
            </p>
            <p>{{ $product->short_description }}</p>

            @if ($product->inStock())
                <p class="ok">In stock ({{ $product->stock }} available)</p>
                <form method="post" action="{{ route('cart.add', $product) }}" class="buy">
                    @csrf
                    <input type="number" name="quantity" value="1" min="1" max="{{ min($product->stock, 99) }}" aria-label="Quantity">
                    <button class="btn btn-lg" type="submit">Add to cart</button>
                </form>
            @else
                <p class="bad">Out of stock</p>
            @endif

            @if ($product->description)
                <h2>Description</h2>
                <div class="prose">{!! nl2br(e($product->description)) !!}</div>
            @endif
        </div>
    </div>

    @if ($related->isNotEmpty())
        <h2>Related products</h2>
        <div class="grid">
            @foreach ($related as $item)<x-product-card :product="$item" />@endforeach
        </div>
    @endif
</x-shop>
