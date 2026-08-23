<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;

class MarketplaceController extends Controller
{
    public function home(Request $request)
    {
        $values = $request->validate([
            'search' => ['nullable', 'string', 'max:120'],
            'category' => ['nullable', 'integer', 'exists:categories,id'],
        ]);

        $base = Product::query()
            ->where('status', 'published')
            ->with([
                'author:id,name',
                'category:id,name,slug',
                'leastPrice' => fn ($query) => $query->select('product_licenses.id', 'product_licenses.product_id', 'product_licenses.license_id', 'product_licenses.price'),
                'leastPrice.license:id,name,slug',
            ]);

        $products = (clone $base)
            ->when($values['search'] ?? null, fn ($query, $search) => $query->where('name', 'like', '%'.$search.'%'))
            ->when($values['category'] ?? null, fn ($query, $category) => $query->where('category_id', $category))
            ->latest()
            ->take(12)
            ->get();

        $categories = Category::query()
            ->where('status', true)
            ->withCount(['products as published_products_count' => fn ($query) => $query->where('status', 'published')])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->take(8)
            ->get(['id', 'name', 'slug', 'description', 'icon']);

        return response()->json([
            'categories' => $categories,
            'featured' => (clone $base)->orderByDesc('sales')->orderByDesc('rating')->take(4)->get(),
            'products' => $products,
        ]);
    }
}
