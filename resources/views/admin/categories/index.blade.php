<x-admin title="Categories">
    <p><a class="btn" href="{{ route('admin.categories.create') }}">Add category</a></p>
    <div class="card">
        <table class="table">
            <thead><tr><th>Name</th><th>Slug</th><th>Products</th><th>Order</th><th>Status</th><th></th></tr></thead>
            <tbody>
            @forelse ($categories as $category)
                <tr>
                    <td>{{ $category->name }}</td>
                    <td>{{ $category->slug }}</td>
                    <td>{{ $category->products_count }}</td>
                    <td>{{ $category->sort_order }}</td>
                    <td><span class="pill {{ $category->is_active ? 'pill-completed' : 'pill-cancelled' }}">{{ $category->is_active ? 'Active' : 'Hidden' }}</span></td>
                    <td class="actions">
                        <a href="{{ route('admin.categories.edit', $category) }}">Edit</a>
                        <form method="post" action="{{ route('admin.categories.destroy', $category) }}" onsubmit="return confirm('Delete this category? Its products are kept.')">
                            @csrf @method('DELETE')<button class="link danger" type="submit">Delete</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="muted">No categories yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</x-admin>
