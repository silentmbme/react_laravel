<?php
namespace App\Services;
use App\Models\FinancialAuditLog;
use App\Models\FinancialTransaction;
use App\Models\Payout;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PayoutService {
 /** Reserves currently eligible seller earnings. It never transmits money or marks a payout completed. */
 public function request(int $sellerId, string $currency, ?string $method=null): Payout {
  return DB::transaction(function() use($sellerId,$currency,$method) {
   $earnings=FinancialTransaction::query()->where('seller_id',$sellerId)->where('currency',$currency)->where('type','SELLER_EARNING')->where('status','completed')->whereDoesntHave('payoutItem')->lockForUpdate()->get();
   if($earnings->isEmpty()) throw ValidationException::withMessages(['payout'=>['No eligible seller earnings are available for payout.']]);
   $amount=$earnings->reduce(fn($sum,$earning)=>bcadd($sum,(string)$earning->net_amount,2),'0.00');
   $payout=Payout::create(['payout_id'=>(string)Str::uuid(),'seller_id'=>$sellerId,'amount'=>$amount,'currency'=>$currency,'status'=>'pending','payout_method'=>$method,'requested_at'=>now()]);
   foreach($earnings as $earning) $payout->items()->create(['financial_transaction_id'=>$earning->id,'amount'=>$earning->net_amount,'currency'=>$currency]);
   FinancialAuditLog::create(['action'=>'payout.requested','entity_type'=>Payout::class,'entity_id'=>$payout->id,'new_values'=>['payout_id'=>$payout->payout_id,'seller_id'=>$sellerId,'amount'=>$amount,'currency'=>$currency]]);
   return $payout->load('items');
  });
 }
}
