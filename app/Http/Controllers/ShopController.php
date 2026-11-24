<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;

class ShopController extends Controller
{
    private const SORTS = [
        'newest' => ['created_at', 'desc'],
        'price_asc' => ['price', 'asc'],
        'price_desc' => ['price', 'desc'],
        'name' => ['name', 'asc'],
    ];

    public function index(Request $request, ?Category $category = null)
    {
        $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'sort' => ['nullable', 'in:'.implode(',', array_keys(self::SORTS))],
        ]);

        [$column, $direction] = self::SORTS[$request->query('sort', 'newest')];

        $products = Product::active()
            ->with('category')
            ->when($category, fn ($q) => $q->where('category_id', $category->id))
            ->search($request->query('q'))
            ->orderBy($column, $direction)
            ->paginate(config('shop.per_page'))
            ->withQueryString();

        return view('shop.index', [
            'products' => $products,
            'category' => $category,
            'categories' => Category::active()->orderBy('sort_order')->get(),
            'sort' => $request->query('sort', 'newest'),
            'term' => $request->query('q'),
        ]);
    }

    public function show(Product $product)
    {
        abort_unless($product->is_active, 404);

        return view('shop.show', [
            'product' => $product->load('category'),
            'related' => Product::active()
                ->where('category_id', $product->category_id)
                ->whereKeyNot($product->id)
                ->take(4)
                ->get(),
        ]);
    }
}
