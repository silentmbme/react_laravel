<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\CustomRole;
use App\Models\User;
use App\Models\UserPermission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
class AgentController extends Controller {
 private function authorize(Request $request):void{abort_unless($request->user()?->role==='superadmin',403,'Superadmin access required.');}
 private function assignableRoles(): array { return array_merge(['admin','reviewer'],CustomRole::where('is_staff',true)->where('is_active',true)->pluck('slug')->all()); }
 private function visibleStaffRoles(): array { return array_merge(['superadmin'], $this->assignableRoles()); }
 public function index(Request $request){$this->authorize($request);$v=$request->validate(['search'=>['nullable','string','max:120'],'per_page'=>['nullable','integer','min:5','max:100']]);$roles=$this->visibleStaffRoles();$q=User::whereIn('role',$roles)->with(['permissions','customRole'])->latest();if(!empty($v['search']))$q->where(fn($x)=>$x->where('name','like','%'.$v['search'].'%')->orWhere('email','like','%'.$v['search'].'%'));return $q->paginate($v['per_page']??20);}
 public function roles(Request $request){$this->authorize($request);return response()->json(['builtin'=>[['slug'=>'admin','name'=>'Administrator','permissions'=>['admin.access','review.products']],['slug'=>'reviewer','name'=>'Reviewer','permissions'=>['review.products']]],'custom'=>CustomRole::where('is_staff',true)->where('is_active',true)->orderBy('name')->get()]);}
 public function storeRole(Request $request){$this->authorize($request);$v=$request->validate(['name'=>['required','string','max:80'],'permissions'=>['required','array','min:1'],'permissions.*'=>['distinct',Rule::in(['admin.access','review.products'])]]);$role=CustomRole::create(['name'=>$v['name'],'slug'=>Str::slug($v['name'],'_'),'is_staff'=>true,'is_active'=>true,'permissions'=>$v['permissions']]);return response()->json(['role'=>$role],201);}
 public function store(Request $request){$this->authorize($request);$v=$request->validate(['name'=>['required','string','max:120'],'email'=>['required','email','max:255','unique:users,email'],'password'=>['required','string','min:8'],'role'=>['required',Rule::in($this->assignableRoles())],'permissions'=>['array'],'permissions.*'=>['in:review.products']]);$user=User::create(['name'=>$v['name'],'email'=>$v['email'],'password'=>Hash::make($v['password']),'role'=>$v['role']]);$this->sync($user,$v['permissions']??[]);return response()->json(['agent'=>$user->load(['permissions','customRole'])],201);}
 public function update(Request $request,User $user){$this->authorize($request);abort_unless($user->isStaff() && $user->role!=='superadmin',422,'Only staff agents can be managed here.');$v=$request->validate(['role'=>['required',Rule::in($this->assignableRoles())],'permissions'=>['array'],'permissions.*'=>['in:review.products']]);$user->update(['role'=>$v['role']]);$this->sync($user,$v['permissions']??[]);return response()->json(['agent'=>$user->fresh(['permissions','customRole'])]);}
 private function sync(User $user,array $permissions):void{UserPermission::where('user_id',$user->id)->where('permission','review.products')->delete();if(in_array('review.products',$permissions,true))UserPermission::create(['user_id'=>$user->id,'permission'=>'review.products']);}
}