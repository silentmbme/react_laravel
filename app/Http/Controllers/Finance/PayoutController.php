<?php
namespace App\Http\Controllers\Finance;
use App\Http\Controllers\Controller;
use App\Models\Payout;
use App\Services\PayoutService;
use Illuminate\Http\Request;

class PayoutController extends Controller {
 private function authorize(Request $request): void { abort_unless($request->user()?->role === 'superadmin',403,'Superadmin access required.'); }
 public function index(Request $request) { $this->authorize($request); $v=$request->validate(['seller_id'=>['nullable','integer'],'status'=>['nullable','in:pending,processing,completed,failed,cancelled,reversed'],'currency'=>['nullable','size:3'],'per_page'=>['nullable','integer','min:5','max:100']]); return Payout::with(['seller:id,name,email','items'])->when($v['seller_id']??null,fn($q,$id)=>$q->where('seller_id',$id))->when($v['status']??null,fn($q,$status)=>$q->where('status',$status))->when($v['currency']??null,fn($q,$currency)=>$q->where('currency',strtoupper($currency)))->latest()->paginate($v['per_page']??25); }
 public function request(Request $request, PayoutService $payouts) { $this->authorize($request); $v=$request->validate(['seller_id'=>['required','exists:users,id'],'currency'=>['required','size:3'],'payout_method'=>['nullable','string','max:50']]); return response()->json(['payout'=>$payouts->request($v['seller_id'],strtoupper($v['currency']),$v['payout_method']??null)],201); }
}
