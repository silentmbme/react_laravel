<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
class UsersController extends Controller {
 public function index(Request $request) { $v=$request->validate(['per_page'=>['nullable','integer','min:5','max:100'],'search'=>['nullable','string','max:120'],'role'=>['nullable','in:customer,author']]); return User::query()->whereIn('role',['customer','author'])->when($v['role']??null,fn($q,$role)=>$q->where('role',$role))->when($v['search']??null,fn($q,$search)=>$q->where(fn($nested)=>$nested->where('name','like',"%{$search}%")->orWhere('email','like',"%{$search}%")))->latest()->paginate($v['per_page']??10); }
}