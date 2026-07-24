<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;

class CategoryController extends Controller
{
    public function index(Request $request)
    {

        $perPage =
            $request->per_page ?? 10;

        $search =
            $request->search;

        $query = Category::query()->with('parent');

        if ($search) {

            $query->where(
                'name',
                'like',
                "%{$search}%"
            );
        }

        return $query
            ->paginate($perPage);

        // $categories = Category::with('parent')
        //     ->latest()
        //     ->paginate(10);

        // return response()->json($categories);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|max:150',
            'parent_id' => 'nullable|exists:categories,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        Category::updateOrCreate(['id' => $request->id], [
            'parent_id' => $request->parent_id,
            'name' => $request->name,
            'slug' => Str::slug($request->name),
            'description' => $request->description,
            'status' => true
        ]);

        return response()->json([
            'message' => 'Category created successfully'
        ]);
    }

    public function edit($id)
    {
        return response()->json(
            Category::find($id)
        );
    }

    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|max:150',
            'parent_id' => 'nullable|exists:categories,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        Category::updateOrCreate(['id' => $request->id], [
            'parent_id' => $request->parent_id,
            'name' => $request->name,
            'slug' => Str::slug($request->name),
            'description' => $request->description,
            'status' => true
        ]);

        return response()->json([
            'message' => 'Category created successfully'
        ]);
    }

    function delete($id){
        Category::find($id)->delete();
        return response()->json([
            'message'=> 'Category deleted successfully'
        ]);
    }

    public function parents()
    {

        return response()->json(
            $this->buildTree(
                Category::where('status', 1)
                    ->get()
            )
        );
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
