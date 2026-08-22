<?php

namespace App\Http\Controllers\Author;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PortofolioController extends Controller
{

    public function portfolio(Request $request)
    {
        $products = Product::query()
            ->with('licenses','images','leastPrice')
            ->when($request->search, function ($query) use ($request) {

                $query->where(
                    'name',
                    'like',
                    '%' . $request->search . '%'
                );
            })

            ->when($request->category, function ($query) use ($request) {

                $query->where(
                    'category_id',
                    $request->category
                );
            })

            ->latest()

            ->paginate(12);

        dd($products);

        return response()->json([
            'author' => Auth::user(),
            'products' => $products,
        ]);
    }
}
