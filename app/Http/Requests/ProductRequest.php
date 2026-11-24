<?php

namespace App\Http\Requests;

use App\Support\Money;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductRequest extends FormRequest
{
    public function rules(): array
    {
        $id = $this->route('product')?->id;

        return [
            'category_id' => ['nullable', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:190'],
            'slug' => ['nullable', 'string', 'max:190', 'alpha_dash', Rule::unique('products', 'slug')->ignore($id)],
            'sku' => ['required', 'string', 'max:60', Rule::unique('products', 'sku')->ignore($id)],
            'short_description' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:10000'],
            'price' => ['required', 'numeric', 'min:0'],
            'sale_price' => ['nullable', 'numeric', 'min:0', 'lt:price'],
            'stock' => ['required', 'integer', 'min:0'],
            'image' => ['nullable', 'image', 'max:2048'],
            'is_active' => ['boolean'],
            'is_featured' => ['boolean'],
        ];
    }

    /** Validated data with prices converted to cents and checkboxes to booleans. */
    public function productData(): array
    {
        $data = $this->safe()->except('image');

        $data['price'] = Money::toCents($data['price']);
        $data['sale_price'] = filled($data['sale_price'] ?? null) ? Money::toCents($data['sale_price']) : null;
        $data['is_active'] = $this->boolean('is_active');
        $data['is_featured'] = $this->boolean('is_featured');
        $data['slug'] = ($data['slug'] ?? null) ?: null;

        return $data;
    }
}
