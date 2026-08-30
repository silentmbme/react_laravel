<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;use App\Models\License;use Illuminate\Http\Request;use Illuminate\Support\Str;
class LicenseController extends Controller {
 public function index(){return response()->json(['licenses'=>License::orderBy('sort_order')->orderBy('name')->get()]);}
 public function store(Request $request){$v=$request->validate(['name'=>['required','string','max:120','unique:licenses,name'],'status'=>['nullable','boolean']]);$license=License::create(['name'=>$v['name'],'slug'=>Str::slug($v['name']),'status'=>$v['status']??true,'sort_order'=>(int)License::max('sort_order')+1]);return response()->json(['license'=>$license,'message'=>'License created.'],201);}
 public function update(Request $request,License $license){$v=$request->validate(['name'=>['required','string','max:120','unique:licenses,name,'.$license->id],'status'=>['required','boolean']]);$license->update(['name'=>$v['name'],'slug'=>Str::slug($v['name']),'status'=>$v['status']]);return response()->json(['license'=>$license->fresh(),'message'=>'License updated.']);}
 public function reorder(Request $request){$v=$request->validate(['ids'=>['required','array','min:1'],'ids.*'=>['integer','distinct','exists:licenses,id']]);abort_unless(count($v['ids'])===License::count(),422,'Send every license when changing order.');foreach($v['ids'] as $index=>$id)License::whereKey($id)->update(['sort_order'=>$index+1]);return response()->json(['message'=>'License order saved.','licenses'=>License::orderBy('sort_order')->get()]);}
}
