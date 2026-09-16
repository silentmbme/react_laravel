<?php
namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\CartItem;
use App\Models\ProductLicense;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function index(Request $request)
    {
        $items = CartItem::query()->where('user_id', $request->user()->id)
            ->whereHas('product', fn ($query) => $query->where('status', 'published'))
            ->with(['product:id,author_id,category_id,name,slug,thumbnail,status','product.author:id,name','product.category:id,name','product.category.licenses:id,name','productLicense:id,product_id,license_id,price','productLicense.license:id,name,slug'])
            ->latest()->get()->map(function (CartItem $item) {
                $basePrice = (float) $item->productLicense?->price;
                $category = $item->product?->category;
                $config = $category?->licenses?->firstWhere('id', $item->productLicense?->license_id);
                $fee = (float) ($config?->pivot?->buyer_fee_value ?? 0);
                $unitPrice = round($basePrice + (($config?->pivot?->buyer_fee_type ?? 'fixed') === 'percent' ? $basePrice * $fee / 100 : $fee), 2);
                return ['id'=>$item->id,'product'=>$item->product,'license'=>$item->productLicense?->license,'quantity'=>(int)$item->quantity,'unit_price'=>$unitPrice,'price'=>round($unitPrice*$item->quantity,2),'created_at'=>$item->created_at];
            })->values();
        return response()->json(['items'=>$items,'count'=>$items->sum('quantity'),'subtotal'=>round($items->sum('price'),2),'currency'=>'USD']);
    }

    public function store(Request $request)
    {
        $data = $request->validate(['product_license_id'=>['required','integer','exists:product_licenses,id']]);
        abort_unless(in_array($request->user()->role, ['customer', 'author'], true), 403, 'Only customer and author accounts can purchase products.');
        $productLicense = ProductLicense::query()->with('product:id,author_id,status')->findOrFail($data['product_license_id']);
        abort_unless($productLicense->product?->status === 'published', 422, 'This product is not available to purchase.');
        abort_unless($productLicense->product?->author_id !== $request->user()->id, 422, 'Authors cannot purchase their own products.');
        $item = CartItem::query()->where('user_id',$request->user()->id)->where('product_id',$productLicense->product_id)->first();
        if (!$item) $item=CartItem::create(['user_id'=>$request->user()->id,'product_id'=>$productLicense->product_id,'product_license_id'=>$productLicense->id,'quantity'=>1]);
        elseif ($item->product_license_id === $productLicense->id) $item->update(['quantity'=>min(99,$item->quantity+1)]);
        else $item->update(['product_license_id'=>$productLicense->id,'quantity'=>1]);
        return response()->json(['message'=>'Item added to your cart.','item_id'=>$item->id,'quantity'=>(int) $item->quantity],201);
    }

    public function update(Request $request, CartItem $cartItem)
    {
        abort_unless($cartItem->user_id === $request->user()->id, 404);
        $data = $request->validate(['quantity'=>['nullable','integer','min:1','max:99'],'product_license_id'=>['nullable','integer','exists:product_licenses,id']]);
        abort_unless(in_array($request->user()->role, ['customer', 'author'], true), 403, 'Only customer and author accounts can purchase products.');
        abort_unless(!empty($data), 422, 'Choose a quantity or license.');
        if (!empty($data['product_license_id'])) {
            $license=ProductLicense::query()->with('product:id,author_id,status')->findOrFail($data['product_license_id']);
            abort_unless($license->product_id === $cartItem->product_id && $license->product?->status === 'published' && $license->product?->author_id !== $request->user()->id,422,'Authors cannot purchase their own products.');
        }
        $cartItem->update($data);
        return response()->json(['message'=>'Cart updated.']);
    }

    public function destroy(Request $request, CartItem $cartItem)
    {
        abort_unless($cartItem->user_id === $request->user()->id,404);$cartItem->delete();return response()->noContent();
    }
}
