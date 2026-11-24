@php($editing = $product->exists)
<x-admin :title="$editing ? 'Edit product' : 'New product'">
    <form method="post" enctype="multipart/form-data" class="card pad"
          action="{{ $editing ? route('admin.products.update', $product) : route('admin.products.store') }}">
        @csrf
        @if ($editing) @method('PUT') @endif

        <div class="two">
            <x-field name="name" label="Name" :value="$product->name" required />
            <x-field name="sku" label="SKU" :value="$product->sku" required />
        </div>
        <div class="two">
            <x-field name="slug" label="URL slug" :value="$product->slug" hint="Leave blank to generate from the name" />
            <div class="field">
                <label for="category_id">Category</label>
                <select id="category_id" name="category_id">
                    <option value="">None</option>
                    @foreach ($categories as $c)
                        <option value="{{ $c->id }}" @selected((string) old('category_id', $product->category_id) === (string) $c->id)>{{ $c->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="three">
            <x-field name="price" label="Price" type="number" step="0.01" min="0" :value="$editing ? \App\Support\Money::toInput($product->price) : null" required />
            <x-field name="sale_price" label="Sale price" type="number" step="0.01" min="0" :value="$product->sale_price !== null ? \App\Support\Money::toInput($product->sale_price) : null" hint="Optional, must be lower than the price" />
            <x-field name="stock" label="Stock" type="number" min="0" :value="$product->stock ?? 0" required />
        </div>
        <x-field name="short_description" label="Short description" :value="$product->short_description" />
        <div class="field">
            <label for="description">Description</label>
            <textarea id="description" name="description" rows="6">{{ old('description', $product->description) }}</textarea>
            @error('description')<small class="error">{{ $message }}</small>@enderror
        </div>
        <div class="field">
            <label for="image">Image</label>
            @if ($product->image_url)<img class="mini" src="{{ $product->image_url }}" alt="">@endif
            <input id="image" name="image" type="file" accept="image/*">
            <small class="hint">JPG or PNG, up to 2 MB. Run <code>php artisan storage:link</code> once so images are public.</small>
            @error('image')<small class="error">{{ $message }}</small>@enderror
        </div>
        <label class="radio"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $product->is_active ?? true))> Visible in the store</label>
        <label class="radio"><input type="checkbox" name="is_featured" value="1" @checked(old('is_featured', $product->is_featured))> Featured on the home page</label>

        <button class="btn" type="submit">Save product</button>
        <a href="{{ route('admin.products.index') }}">Cancel</a>
    </form>
</x-admin>
