<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;use App\Models\Category;use App\Models\License;use Illuminate\Http\Request;use Illuminate\Support\Facades\Validator;use Illuminate\Support\Str;
class CategoryController extends Controller {
 public function index(Request $request){$q=Category::with('parent')->withCount('licenses');if($request->filled('search'))$q->where('name','like','%'.$request->search.'%');return $q->orderBy('sort_order')->orderBy('name')->paginate($request->integer('per_page',10));}
 public function formOptions(){return response()->json(['licenses'=>License::where('status',true)->orderBy('sort_order')->orderBy('name')->get(['id','name','slug'])]);}
 public function store(Request $request){[$values,$licenses]=$this->validatePayload($request);$category=Category::create($values);$category->licenses()->sync($licenses);return response()->json(['message'=>'Category created successfully','category'=>$category->load('licenses')],201);}
 public function edit($id){return response()->json(Category::with('licenses:id,name,slug')->findOrFail($id));}
 public function update(Request $request){$category=Category::findOrFail($request->id);[$values,$licenses]=$this->validatePayload($request,$category->id);$category->update($values);$category->licenses()->sync($licenses);return response()->json(['message'=>'Category updated successfully','category'=>$category->fresh('licenses')]);}
 public function delete($id){Category::findOrFail($id)->delete();return response()->json(['message'=>'Category deleted successfully']);}
 public function parents(){return response()->json($this->buildTree(Category::where('status',true)->orderBy('sort_order')->orderBy('name')->get()));}
 private function validatePayload(Request $request,?int $categoryId=null):array {
  $validator=Validator::make($request->all(),['name'=>['required','string','max:150'],'parent_id'=>['nullable','exists:categories,id','different:id'],'description'=>['nullable','string'],'license_ids'=>['array'],'license_ids.*'=>['integer','exists:licenses,id'],'license_fees'=>['array'],'license_fees.*.type'=>['nullable','in:fixed,percent'],'license_fees.*.value'=>['nullable','numeric','min:0','max:999999.99']]);
  if($validator->fails())abort(response()->json(['message'=>'Please correct the highlighted category fields.','errors'=>$validator->errors()],422));
  $values=$validator->validated();$ids=$values['license_ids']??[];$fees=$values['license_fees']??[];unset($values['license_ids'],$values['license_fees']);
  $sync=[];foreach($ids as $id){$fee=$fees[$id]??[];$sync[$id]=['buyer_fee_type'=>$fee['type']??'fixed','buyer_fee_value'=>$fee['value']??0];}
  $values['slug']=Str::slug($values['name']);$values['status']=true;if(!empty($values['parent_id']))$sync=[];return [$values,$sync];
 }
 private function buildTree($categories,$parentId=null,$level=0):array{$out=[];foreach($categories->where('parent_id',$parentId) as $category){$category->display_name=str_repeat('— ',$level).$category->name;$out[]=$category;$out=array_merge($out,$this->buildTree($categories,$category->id,$level+1));}return $out;}
}
