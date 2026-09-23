<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductComment;
use Illuminate\Http\Request;

class ProductCommentController extends Controller
{
    public function index(Product $product)
    {
        abort_unless($product->status === 'published', 404);

        return response()->json([
            'comments' => ProductComment::where('product_id', $product->id)
                ->with(['user:id,name','replies.user:id,name'])
                ->latest()
                ->paginate(20),
        ]);
    }

    public function store(Request $request, Product $product)
    {
        abort_unless($product->status === 'published', 404);
        $user = $request->user();
        abort_unless($user && (int) $product->author_id !== (int) $user->id, 403, 'You cannot comment on your own product.');

        $data = $request->validate(['body' => ['required', 'string', 'max:2000']]);
        $comment = ProductComment::create([
            'product_id' => $product->id,
            'user_id' => $user->id,
            'body' => $data['body'],
        ]);

        return response()->json(['comment' => $comment->load('user:id,name')], 201);
    }
}
