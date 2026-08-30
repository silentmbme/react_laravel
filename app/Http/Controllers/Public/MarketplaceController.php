<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;

class MarketplaceController extends Controller
{
    public function search(Request $request)
    {
        $values = $request->validate(['q'=>['nullable','string','max:120'],'category'=>['nullable','integer','exists:categories,id'],'min_price'=>['nullable','numeric','min:0'],'max_price'=>['nullable','numeric','gte:min_price'],'rating'=>['nullable','numeric','min:1','max:5'],'sales'=>['nullable','in:any,low,medium,high'],'date_added'=>['nullable','in:any,week,month,year'],'sort'=>['nullable','in:match,sales,latest,rating,price_asc,price_desc']]);
        $sort=$values['sort']??'match';
        $base=Product::query()->where('status','published')->with(['author:id,name','category:id,name,slug','category.licenses:id,name,slug','leastPrice'=>fn($q)=>$q->select('product_licenses.id','product_licenses.product_id','product_licenses.license_id','product_licenses.price'),'leastPrice.license:id,name,slug','licenses:id,product_id,license_id,price','licenses.license:id,name,slug']);
        $q=trim($values['q']??'');
        if($q!=='')$base->where(fn($query)=>$query->where('name','like','%'.$q.'%')->orWhere('short_description','like','%'.$q.'%')->orWhere('description','like','%'.$q.'%'));
        $base->when($values['category']??null,fn($query,$category)=>$query->where('category_id',$category))->when($values['rating']??null,fn($query,$rating)=>$query->where('rating','>=',$rating)); if(($values['date_added']??'any')!=='any'){$days=match($values['date_added']){'week'=>7,'month'=>31,default=>365};$base->where('created_at','>=',now()->subDays($days));}
        if(isset($values['min_price']))$base->whereHas('licenses',fn($query)=>$query->where('price','>=',$values['min_price']));
        if(isset($values['max_price']))$base->whereHas('licenses',fn($query)=>$query->where('price','<=',$values['max_price']));
        match($sort){'sales'=>$base->orderByDesc('sales'),'latest'=>$base->latest(),'rating'=>$base->orderByDesc('rating')->orderByDesc('sales'),'price_asc'=>$base->orderByRaw('(select min(price) from product_licenses where product_licenses.product_id=products.id) asc'),'price_desc'=>$base->orderByRaw('(select min(price) from product_licenses where product_licenses.product_id=products.id) desc'),default=>$base->when($q!=='',fn($query)=>$query->orderByRaw('CASE WHEN name LIKE ? THEN 0 ELSE 1 END',["%$q%"]))->orderByDesc('sales')->orderByDesc('rating')};
        return response()->json(['categories'=>Category::where('status',true)->orderBy('name')->get(['id','name']),'products'=>$base->paginate(16)->withQueryString()]);
    }
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
                'category:id,name,slug', 'category.licenses:id,name,slug',
                'leastPrice' => fn ($query) => $query->select('product_licenses.id', 'product_licenses.product_id', 'product_licenses.license_id', 'product_licenses.price'),
                'leastPrice.license:id,name,slug',
                'licenses:id,product_id,license_id,price',
                'licenses.license:id,name,slug',
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
            'support_months' => max(0, min(60, (int) (app(\App\Services\MarketplaceSettings::class)->group('billing')['support_months'] ?? 6))),
            'categories' => $categories,
            'featured' => (clone $base)->orderByDesc('sales')->orderByDesc('rating')->take(4)->get(),
            'products' => $products,
        ]);
    }
}
