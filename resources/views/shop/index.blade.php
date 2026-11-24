<x-shop :title="$category?->name ?? 'Shop'">
    <div class="shop-layout">
        <aside class="filters card">
            <h3>Categories</h3>
            <ul class="plain">
                <li><a href="{{ route('shop.index') }}" @class(['active' => ! $category])>All products</a></li>
                @foreach ($categories as $c)
                    <li><a href="{{ route('shop.category', $c) }}" @class(['active' => $category?->is($c)])>{{ $c->name }}</a></li>
                @endforeach
            </ul>
        </aside>

        <section>
            <div class="toolbar">
                <h1>{{ $category?->name ?? ($term ? 'Results for "'.$term.'"' : 'All products') }}</h1>
                <form method="get" class="sort">
                    @if ($term)<input type="hidden" name="q" value="{{ $term }}">@endif
                    <label for="sort" class="muted">Sort by</label>
                    <select id="sort" name="sort" onchange="this.form.submit()">
                        @foreach (['newest' => 'Newest', 'price_asc' => 'Price: low to high', 'price_desc' => 'Price: high to low', 'name' => 'Name'] as $key => $label)
                            <option value="{{ $key }}" @selected($sort === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <noscript><button class="btn">Apply</button></noscript>
                </form>
            </div>

            @if ($category?->description)<p class="muted">{{ $category->description }}</p>@endif

            <div class="grid">
                @forelse ($products as $product)
                    <x-product-card :product="$product" />
                @empty
                    <p class="muted">Nothing found. Try a different search.</p>
                @endforelse
            </div>

            {{ $products->links() }}
        </section>
    </div>
</x-shop>
