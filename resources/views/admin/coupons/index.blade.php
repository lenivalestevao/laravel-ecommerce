<x-admin title="Coupons">
    <p><a class="btn" href="{{ route('admin.coupons.create') }}">Add coupon</a></p>
    <div class="card">
        <table class="table">
            <thead><tr><th>Code</th><th>Discount</th><th>Min. order</th><th>Used</th><th>Expires</th><th>Status</th><th></th></tr></thead>
            <tbody>
            @forelse ($coupons as $coupon)
                <tr>
                    <td><strong>{{ $coupon->code }}</strong></td>
                    <td>{{ $coupon->type === 'percent' ? $coupon->value.'%' : money($coupon->value) }}</td>
                    <td>{{ $coupon->min_subtotal ? money($coupon->min_subtotal) : 'None' }}</td>
                    <td>{{ $coupon->used_count }}{{ $coupon->max_uses ? ' / '.$coupon->max_uses : '' }}</td>
                    <td>{{ $coupon->expires_at?->format('M j, Y') ?? 'Never' }}</td>
                    <td><span class="pill {{ $coupon->is_active ? 'pill-completed' : 'pill-cancelled' }}">{{ $coupon->is_active ? 'Active' : 'Off' }}</span></td>
                    <td class="actions">
                        <a href="{{ route('admin.coupons.edit', $coupon) }}">Edit</a>
                        <form method="post" action="{{ route('admin.coupons.destroy', $coupon) }}" onsubmit="return confirm('Delete this coupon?')">
                            @csrf @method('DELETE')<button class="link danger" type="submit">Delete</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="muted">No coupons yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    {{ $coupons->links() }}
</x-admin>
