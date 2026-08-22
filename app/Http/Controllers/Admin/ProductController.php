<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->per_page ?? 10;
        $search = $request->search;

        $query = Product::query();

        if ($search) {
            $query->where('name', 'like', "%{$search}%");
        } 

        return $query->paginate($perPage);
    }
}
