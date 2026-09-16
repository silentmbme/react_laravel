<?php
namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\FinancialAuditLog;
use App\Models\FinancialTransaction;
use App\Models\Order;
use App\Models\TaxConfiguration;
use App\Services\RefundService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class FinancialAdminController extends Controller {
 private function authorizeAdmin(Request $request): void { abort_unless(in_array($request->user()?->role,['admin','superadmin'],true),403,'Admin access required.'); }
 public function transactions(Request $request) {
  $this->authorizeAdmin($request); $f=$request->validate(['from'=>['nullable','date'],'to'=>['nullable','date','after_or_equal:from'],'type'=>['nullable','string','max:32'],'status'=>['nullable','string','max:32'],'currency'=>['nullable','size:3'],'seller_id'=>['nullable','integer'],'product_id'=>['nullable','integer'],'search'=>['nullable','string','max:120'],'per_page'=>['nullable','integer','min:5','max:100']]);
  return FinancialTransaction::query()->with(['order.user:id,name,email'])->when($f['from']??null,fn($q,$v)=>$q->whereDate('created_at','>=',$v))->when($f['to']??null,fn($q,$v)=>$q->whereDate('created_at','<=',$v))->when($f['type']??null,fn($q,$v)=>$q->where('type',$v))->when($f['status']??null,fn($q,$v)=>$q->where('status',$v))->when($f['currency']??null,fn($q,$v)=>$q->where('currency',strtoupper($v)))->when($f['seller_id']??null,fn($q,$v)=>$q->where('seller_id',$v))->when($f['product_id']??null,fn($q,$v)=>$q->where('product_id',$v))->when($f['search']??null,function($q,$v){$q->where(fn($x)=>$x->where('transaction_id','like',"%{$v}%")->orWhere('payment_reference','like',"%{$v}%")->orWhereHas('order',fn($o)=>$o->where('public_id','like',"%{$v}%")));})->latest()->paginate($f['per_page']??25);
 }
 public function summary(Request $request) {
  $this->authorizeAdmin($request); $f=$request->validate(['from'=>['nullable','date'],'to'=>['nullable','date','after_or_equal:from']]); $q=FinancialTransaction::query()->when($f['from']??null,fn($q,$v)=>$q->whereDate('created_at','>=',$v))->when($f['to']??null,fn($q,$v)=>$q->whereDate('created_at','<=',$v));
  $sum=fn($type)=> (string)(clone $q)->where('type',$type)->sum('amount');
  return response()->json(['currency'=>'USD','total_sales'=>$sum('SALE'),'refunds'=>abs((float)$sum('REFUND'))+abs((float)$sum('PARTIAL_REFUND')),'taxes'=>(string)(clone $q)->where('type','SALE')->sum('tax_amount'),'payment_fees'=>(string)(clone $q)->where('type','PAYMENT_FEE')->sum('amount'),'platform_revenue'=>(string)(clone $q)->where('type','COMMISSION')->sum('amount'),'seller_earnings'=>(string)(clone $q)->where('type','SELLER_EARNING')->sum('amount'),'today_sales'=>(string)(clone $q)->where('type','SALE')->whereDate('created_at',today())->sum('amount'),'week_sales'=>(string)(clone $q)->where('type','SALE')->where('created_at','>=',now()->startOfWeek())->sum('amount'),'month_sales'=>(string)(clone $q)->where('type','SALE')->where('created_at','>=',now()->startOfMonth())->sum('amount')]);
 }
 public function refund(Request $request, Order $order, RefundService $refunds) { $this->authorizeAdmin($request); $v=$request->validate(['amount'=>['required','regex:/^\d+(\.\d{1,2})?$/'],'reason'=>['nullable','string','max:1000'],'order_item_id'=>['nullable','integer'],'idempotency_key'=>['nullable','uuid']]); return response()->json(['refund'=>$refunds->record($order,number_format((float)$v['amount'],2,'.',''),$v['idempotency_key']??(string)Str::uuid(),$request->user()->id,$v['order_item_id']??null,$v['reason']??null)],201); }
 public function taxes(Request $request) { $this->authorizeAdmin($request); return TaxConfiguration::latest()->paginate(min((int)$request->query('per_page',25),100)); }
 public function storeTax(Request $request) { $this->authorizeAdmin($request); $v=$request->validate(['tax_enabled'=>['required','boolean'],'tax_name'=>['required','string','max:100'],'tax_code'=>['nullable','string','max:50'],'tax_rate'=>['required','numeric','min:0','max:100'],'tax_type'=>['required','in:percentage'],'country'=>['nullable','size:2'],'region'=>['nullable','string','max:100'],'inclusive'=>['required','boolean'],'effective_from'=>['nullable','date'],'effective_until'=>['nullable','date','after_or_equal:effective_from'],'status'=>['required','boolean']]); $tax=TaxConfiguration::create($v); FinancialAuditLog::create(['actor_id'=>$request->user()->id,'action'=>'tax_configuration.created','entity_type'=>TaxConfiguration::class,'entity_id'=>$tax->id,'new_values'=>$tax->toArray(),'ip_address'=>$request->ip()]); return response()->json(['tax'=>$tax],201); }
}
