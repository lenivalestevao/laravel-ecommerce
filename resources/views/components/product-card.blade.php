@props(['product'])

<article class="card product-card">
    <a href="{{ route('shop.show', $product) }}" class="thumb">
        @if ($product->image_url)
            <img src="{{ $product->image_url }}" alt="{{ $product->name }}" loading="lazy">
        @else
            <span class="placeholder" aria-hidden="true">{{ mb_strtoupper(mb_substr($product->name, 0, 1)) }}</span>
        @endif
        @if ($product->onSale())<span class="badge badge-sale">Sale</span>@endif
        @unless ($product->inStock())<span class="badge badge-out">Sold out</span>@endunless
    </a>
    <div class="card-body">
        <small class="muted">{{ $product->category?->name }}</small>
        <h3><a href="{{ route('shop.show', $product) }}">{{ $product->name }}</a></h3>
        <p class="price">
            @if ($product->onSale())<s>{{ money($product->price) }}</s> @endif
            <strong>{{ money($product->current_price) }}</strong>
        </p>
        @if ($product->inStock())
            <form method="post" action="{{ route('cart.add', $product) }}">
                @csrf
                <button class="btn btn-block" type="submit">Add to cart</button>
            </form>
        @else
            <button class="btn btn-block" type="button" disabled>Out of stock</button>
        @endif
    </div>
</article>
