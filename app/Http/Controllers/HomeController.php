<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;

class HomeController extends Controller
{
    public function __invoke()
    {
        return view('home', [
            'featured' => Product::active()->featured()->with('category')->latest()->take(8)->get(),
            'latest' => Product::active()->with('category')->latest()->take(8)->get(),
            'categories' => Category::active()->withCount(['products' => fn ($q) => $q->active()])->orderBy('sort_order')->get(),
        ]);
    }
}
