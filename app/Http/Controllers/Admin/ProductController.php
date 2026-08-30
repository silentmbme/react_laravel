<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;use App\Models\Product;use App\Models\UserPermission;use Illuminate\Http\Request;use Illuminate\Support\Facades\Auth;
class ProductController extends Controller{
 private function authorizeAdmin(){ $u=Auth::user(); abort_unless($u && ($u->role==='superadmin'||$u->role==='admin'||UserPermission::where('user_id',$u->id)->where('permission','review.products')->exists()),403,'Review permission required.');}
 public function index(Request $request){$this->authorizeAdmin();$v=$request->validate(['per_page'=>['nullable','integer','min:1','max:100'],'search'=>['nullable','string'],'status'=>['nullable','in:published,rejected,changes_requested']]);return Product::with(['author:id,name','category:id,name'])->where('status','published')->when($v['search']??null,fn($q,$s)=>$q->where('name','like','%'.$s.'%'))->when($v['status']??null,fn($q,$s)=>$q->where('status',$s))->latest()->paginate($v['per_page']??10);}
 public function updateStatus(Request $request,Product $product){$this->authorizeAdmin();$v=$request->validate(['status'=>['required','in:pending,published,rejected']]);$product->update(['status'=>$v['status']]);return response()->json(['message'=>'Product status updated.','product'=>$product]);}
}