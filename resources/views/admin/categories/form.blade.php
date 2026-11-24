@php($editing = $category->exists)
<x-admin :title="$editing ? 'Edit category' : 'New category'">
    <form method="post" class="card pad narrow"
          action="{{ $editing ? route('admin.categories.update', $category) : route('admin.categories.store') }}">
        @csrf
        @if ($editing) @method('PUT') @endif
        <x-field name="name" label="Name" :value="$category->name" required />
        <x-field name="slug" label="URL slug" :value="$category->slug" hint="Leave blank to generate from the name" />
        <x-field name="sort_order" label="Sort order" type="number" min="0" :value="$category->sort_order ?? 0" />
        <div class="field">
            <label for="description">Description</label>
            <textarea id="description" name="description" rows="3">{{ old('description', $category->description) }}</textarea>
        </div>
        <label class="radio"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $category->is_active ?? true))> Visible in the store</label>
        <button class="btn" type="submit">Save category</button>
        <a href="{{ route('admin.categories.index') }}">Cancel</a>
    </form>
</x-admin>
