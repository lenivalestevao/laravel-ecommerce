@php($editing = $coupon->exists)
<x-admin :title="$editing ? 'Edit coupon' : 'New coupon'">
    <form method="post" class="card pad narrow"
          action="{{ $editing ? route('admin.coupons.update', $coupon) : route('admin.coupons.store') }}">
        @csrf
        @if ($editing) @method('PUT') @endif
        <x-field name="code" label="Code" :value="$coupon->code" required />
        <div class="field">
            <label for="type">Type</label>
            <select id="type" name="type">
                <option value="percent" @selected(old('type', $coupon->type) === 'percent')>Percent off</option>
                <option value="fixed" @selected(old('type', $coupon->type) === 'fixed')>Fixed amount off</option>
            </select>
        </div>
        @php($value = $coupon->exists ? ($coupon->type === 'percent' ? $coupon->value : \App\Support\Money::toInput($coupon->value)) : null)
        <x-field name="value" label="Value" type="number" step="0.01" min="0.01" :value="$value" hint="A percentage (1-100) or an amount, depending on the type" required />
        <x-field name="min_subtotal" label="Minimum order amount" type="number" step="0.01" min="0" :value="$coupon->min_subtotal ? \App\Support\Money::toInput($coupon->min_subtotal) : null" />
        <x-field name="max_uses" label="Maximum uses" type="number" min="1" :value="$coupon->max_uses" hint="Leave blank for unlimited" />
        <x-field name="expires_at" label="Expires on" type="date" :value="$coupon->expires_at?->format('Y-m-d')" />
        <label class="radio"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $coupon->is_active ?? true))> Active</label>
        <button class="btn" type="submit">Save coupon</button>
        <a href="{{ route('admin.coupons.index') }}">Cancel</a>
    </form>
</x-admin>
