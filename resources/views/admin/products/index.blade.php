<x-admin title="Products">
    <div class="toolbar">
        <form method="get" class="search"><input type="search" name="q" value="{{ $term }}" placeholder="Search name or SKU"><button class="btn">Search</button></form>
        <a class="btn" href="{{ route('admin.products.create') }}">Add product</a>
    </div>
    <div class="card">
        <table class="table">
            <thead><tr><th>Name</th><th>SKU</th><th>Category</th><th>Price</th><th>Stock</th><th>Status</th><th></th></tr></thead>
            <tbody>
            @forelse ($products as $product)
                <tr>
                    <td>{{ $product->name }}</td>
                    <td>{{ $product->sku }}</td>
                    <td>{{ $product->category?->name ?? 'None' }}</td>
                    <td>{{ money($product->current_price) }}</td>
                    <td>{{ $product->stock }}</td>
                    <td><span class="pill {{ $product->is_active ? 'pill-completed' : 'pill-cancelled' }}">{{ $product->is_active ? 'Active' : 'Hidden' }}</span></td>
                    <td class="actions">
                        <a href="{{ route('admin.products.edit', $product) }}">Edit</a>
                        <form method="post" action="{{ route('admin.products.destroy', $product) }}" onsubmit="return confirm('Delete this product?')">
                            @csrf @method('DELETE')<button class="link danger" type="submit">Delete</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="muted">No products found.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    {{ $products->links() }}
</x-admin>
