<?php
namespace App\Services;
use App\Models\AuthorEarning; use App\Models\CartItem; use App\Models\CommissionTier; use App\Models\DownloadEntitlement; use App\Models\Order; use App\Models\Product; use App\Models\PurchaseVerification; use App\Notifications\OrderPaidNotification; use Illuminate\Support\Facades\Crypt; use Illuminate\Support\Facades\DB; use Illuminate\Support\Str;
class OrderFulfillmentService {
 public function markPaid(Order $order, ?string $providerReference=null): Order {
  return DB::transaction(function()use($order,$providerReference){
   $order=Order::query()->with(['items.product','user'])->lockForUpdate()->findOrFail($order->id);
   if($order->status==='paid') return $order;
   $order->update(['status'=>'paid','provider_reference'=>$providerReference?:$order->provider_reference,'paid_at'=>now()]);
   foreach($order->items as $item){
    for($unit=1;$unit<=(int)$item->quantity;$unit++){DownloadEntitlement::firstOrCreate(['user_id'=>$order->user_id,'order_item_id'=>$item->id,'unit_number'=>$unit]);$this->recordPurchaseCode($item,$unit);}
    Product::query()->whereKey($item->product_id)->increment('sales',$item->quantity);
    CartItem::query()->where('user_id',$order->user_id)->where('product_id',$item->product_id)->where('product_license_id',$item->product_license_id)->delete();
    $this->recordAuthorEarning($order,$item);
   }
   DB::afterCommit(fn()=>$order->user?->notify(new OrderPaidNotification($order->id)));
   return $order->fresh('items');
  });
 }
 private function recordPurchaseCode($item,int $unit):void{$product=$item->product;if(!$product)return;$code=Str::upper(Str::random(8).'-'.Str::random(8).'-'.Str::random(8).'-'.Str::random(8));PurchaseVerification::firstOrCreate(['order_item_id'=>$item->id,'unit_number'=>$unit],['code_hash'=>hash('sha256',$code),'code_encrypted'=>Crypt::encryptString($code),'support_until'=>$product->support_enabled?now()->addMonths(max(0,min(60,(int)(app(MarketplaceSettings::class)->group('billing')['support_months']??6)))):null]);}
 private function recordAuthorEarning(Order $order,$item):void{$authorId=$item->product?->author_id;if(!$authorId)return;$lifetime=(float)AuthorEarning::where('author_id',$authorId)->where('currency',$order->currency)->sum('gross_amount');$tier=CommissionTier::where('is_active',true)->where('min_lifetime_sales','<=',$lifetime)->orderByDesc('min_lifetime_sales')->first();$gross=round((float)$item->unit_price*(int)$item->quantity,2);$percent=(float)($tier?->platform_fee_percent??0);$fee=round($gross*$percent/100,2);AuthorEarning::firstOrCreate(['order_item_id'=>$item->id],['author_id'=>$authorId,'order_id'=>$order->id,'commission_tier_name'=>$tier?->name,'gross_amount'=>$gross,'platform_fee_amount'=>$fee,'net_amount'=>round($gross-$fee,2),'currency'=>$order->currency,'status'=>'available','available_at'=>now()]);}
}