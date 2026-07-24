<?php

namespace App\Http\Controllers\Author;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\License;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductLicense;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;


class ProductController extends Controller
{
    public function create()
    {
        return response()->json([

            'categories' =>  $this->buildTree(
                Category::where('status', 1)
                    ->orderBy('name')
                    ->get()
            ),

            'licenses' => License::where('status', 1)->get()

        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "name" =>  'required',
            "category_id" =>  'required',
            "short_description" =>  'required',
            "description" =>  'required',
            "thumbnail" =>  'required',
            "preview_images" =>  'required',
            "file" =>  'required',
            "version" =>  'required',
            "demo_url" =>  'required',
            "status" =>  'required',
            'licenses' => 'required|array|min:1',
            'licenses.*' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()]);
        }

        $licenseIds = array_keys($request->licenses);

        $validLicenseIds = License::whereIn('id', $licenseIds)
            ->pluck('id')
            ->toArray();

        if (count($licenseIds) !== count($validLicenseIds)) {

            return response()->json([
                'errors' => [
                    'licenses' => ['One or more selected licenses are invalid.']
                ]
            ], 422);
        }
        // return response()->json($request->all());

        $product = Product::updateOrCreate(['id' => $request->id], [
            'author_id' => Auth::user()->id,
            'category_id' => $request->category_id,
            'name' => $request->name,
            'slug' => Str::slug($request->name),
            'short_description' => $request->short_description,
            'description' => $request->description,
            'thumbnail' => $request->thumbnail,
            'file' => $request->file,
            'file_size' => $request->file_size,
            'version' => $request->version,
            'demo_url' => $request->demo_url,
            'status'  => $request->status,
        ]);

        $images = [];

        foreach ($request->preview_images as $image) {

            $images[] = [
                'product_id' => $product->id,
                'image' => $image, // or 'path' => $image
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        ProductImage::insert($images);


        $licenses = [];

        foreach ($request->licenses as $licenseId => $price) {

            $licenses[] = [
                'product_id' => $product->id,
                'license_id' => $licenseId,
                'price' => $price,
                // 'created_at' => now(),
                // 'updated_at' => now(),
            ];
        }

        ProductLicense::insert($licenses);

        return response()->json($request->all());
    }

    private function buildTree($categories, $parentId = null, $level = 0)
    {
        $result = [];

        foreach (
            $categories->where('parent_id', $parentId)
            as $category
        ) {

            $category->display_name =
                str_repeat('— ', $level)
                . $category->name;

            $result[] = $category;

            $children = $this->buildTree(
                $categories,
                $category->id,
                $level + 1
            );

            $result = array_merge(
                $result,
                $children
            );
        }

        return $result;
    }
}
