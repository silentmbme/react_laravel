<?php

namespace App\Http\Controllers\Author;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PortofolioController extends Controller
{
    public function portfolio(Request $request)
    {
        $values = $request->validate([
            'search' => ['nullable', 'string'],
            'category' => ['nullable', 'integer'],
            'sort' => ['nullable', 'in:latest,sales,rating'],
        ]);
        $author = Auth::user();
        $sort = $values['sort'] ?? 'latest';

        $products = $this->query($author->id)
            ->where('status', 'published')
            ->when($values['search'] ?? null, fn ($query, $search) => $query->where('name', 'like', '%'.$search.'%'))
            ->when($values['category'] ?? null, fn ($query, $category) => $query->where('category_id', $category))
            ->when($sort === 'sales', fn ($query) => $query->orderByDesc('sales'))
            ->when($sort === 'rating', fn ($query) => $query->orderByDesc('rating'))
            ->when($sort === 'latest', fn ($query) => $query->latest())
            ->paginate(12);

        return response()->json([
            'author' => $this->authorData($author),
            'categories' => Category::where('status', 1)->orderBy('name')->get(['id', 'name']),
            'products' => $products,
        ]);
    }

    public function dashboard()
    {
        $author = Auth::user();
        $reviewProducts = Product::where('author_id', $author->id)
            ->whereIn('status', ['draft', 'pending', 'rejected', 'changes_requested'])
            ->with('category:id,name')->latest()
            ->get(['id', 'name', 'slug', 'thumbnail', 'status', 'category_id', 'updated_at']);

        $stats = Product::where('author_id', $author->id)
            ->selectRaw("SUM(status='published') published,SUM(status='pending') pending,SUM(status='draft') drafts,SUM(status='rejected') rejected,SUM(status='changes_requested') changes_requested,COALESCE(SUM(sales),0) sales,COALESCE(SUM(views),0) views")
            ->first();

        return response()->json([
            'stats' => [
                'published' => (int) $stats->published,
                'pending' => (int) $stats->pending,
                'drafts' => (int) $stats->drafts,
                'rejected' => (int) $stats->rejected,
                'changes_requested' => (int) $stats->changes_requested,
                'sales' => (int) $stats->sales,
                'views' => (int) $stats->views,
            ],
            'review_products' => $reviewProducts,
        ]);
    }

    public function show(Product $product)
    {
        abort_unless($product->author_id === Auth::id(), 404);
        return response()->json(['product' => $this->details($product)]);
    }

    public function publicShow(Product $product)
    {
        abort_unless($product->status === 'published', 404);
        return response()->json(['product' => $this->details($product)]);
    }

    public function publicPortfolio(User $author)
    {
        return response()->json([
            'author' => $this->authorData($author),
            'products' => $this->query($author->id)->where('status', 'published')->latest()->paginate(12),
        ]);
    }

    private function query(int $authorId)
    {
        return Product::where('author_id', $authorId)->with([
            'category:id,name',
            'leastPrice' => fn ($query) => $query->select('product_licenses.id', 'product_licenses.product_id', 'product_licenses.price'),
        ]);
    }

    private function details(Product $product)
    {
        return $product->load([
            'author:id,name',
            'category:id,name',
            'images:id,product_id,image,sort_order',
            'licenses:id,product_id,license_id,price',
            'licenses.license:id,name,slug',
        ]);
    }

    private function authorData(User $author): array
    {
        $stats = Product::where('author_id', $author->id)->where('status', 'published')
            ->selectRaw('COUNT(*) products,COALESCE(SUM(sales),0) sales,COALESCE(AVG(rating),0) rating')->first();

        return [
            'id' => $author->id,
            'name' => $author->name,
            'total_products' => (int) $stats->products,
            'total_sales' => (int) $stats->sales,
            'rating' => round((float) $stats->rating, 1),
        ];
    }
}
