<?php
namespace App\Http\Controllers\Public;
use App\Http\Controllers\Controller;use App\Services\Username;use Illuminate\Http\Request;use Illuminate\Support\Facades\Hash;
class ProfileController extends Controller {
 public function show(Request $request){return response()->json(['user'=>$this->payload($request->user())]);}
 public function update(Request $request){$user=$request->user();$request->merge(['username'=>Username::normalize($request->input('username'))]);$data=$request->validate(['username'=>Username::rules($user->id),'first_name'=>['required','string','max:120'],'last_name'=>['required','string','max:120'],'email'=>['required','email','max:255','unique:users,email,'.$user->id]]);$user->update([...$data,'name'=>trim($data['first_name'].' '.$data['last_name'])]);return response()->json(['message'=>'Profile saved.','user'=>$this->payload($user->fresh())]);}
 public function password(Request $request){$data=$request->validate(['current_password'=>['required','string'],'password'=>['required','string','min:8','confirmed']]);abort_unless(Hash::check($data['current_password'],$request->user()->password),422,'Your current password is incorrect.');$request->user()->update(['password'=>Hash::make($data['password'])]);return response()->json(['message'=>'Password updated.']);}
 private function payload($user):array{return $user->only(['id','username','name','first_name','last_name','email','role','freelance_enabled','freelance_url','created_at']);}
}