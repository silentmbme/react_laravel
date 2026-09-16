<?php
namespace App\Services;
use App\Models\CartItem;use App\Models\Order;use App\Models\OrderItem;use Illuminate\Support\Facades\DB;use Illuminate\Support\Str;use Illuminate\Validation\ValidationException;
class OrderService {
 public function previewCart(int $userId,string $currency,MarketplaceSettings $settings):array {
  $cart=CartItem::where('user_id',$userId)->with(['product.category.licenses','productLicense.license'])->get();
  if($cart->isEmpty())throw ValidationException::withMessages(['cart'=>['Your cart is empty.']]);
  $subtotal=0.0;$buyerFeeTotal=0.0;$items=[];
  foreach($cart as $entry){
   if($entry->product?->status!=='published'||!$entry->productLicense)throw ValidationException::withMessages(['cart'=>['A cart item is no longer available.']]);
   if($entry->product?->author_id===$userId)throw ValidationException::withMessages(['cart'=>['Authors cannot purchase their own products. Remove the item to continue.']]);
   $unitPrice=round((float)$entry->productLicense->price,2);$category=$entry->product->category?->pricingCategory();$config=$category?->licenses?->firstWhere('id',$entry->productLicense->license_id);
   if(!$config)throw ValidationException::withMessages(['cart'=>['This license is no longer available for its category. Please update your cart.']]);
   $fee=(float)($config->pivot->buyer_fee_value??0);$unitBuyerFee=round(($config->pivot->buyer_fee_type??'fixed')==='percent'?$unitPrice*$fee/100:$fee,2);$quantity=max(1,(int)$entry->quantity);
   $subtotal+=$unitPrice*$quantity;$buyerFeeTotal+=$unitBuyerFee*$quantity;$items[]=['id'=>$entry->id,'name'=>$entry->product->name,'license'=>$entry->productLicense->license?->name??'License','quantity'=>$quantity,'unit_price'=>$unitPrice,'unit_buyer_fee'=>$unitBuyerFee,'total'=>round(($unitPrice+$unitBuyerFee)*$quantity,2)];
  }
  $handling=$this->handlingFee(round($subtotal+$buyerFeeTotal,2),$settings->group('billing'));
  $tax=app(TaxCalculationService::class)->calculate(number_format($subtotal+$buyerFeeTotal+$handling,2,'.',''),$currency);return ['currency'=>$currency,'items'=>$items,'subtotal'=>round($subtotal,2),'discount_total'=>0.0,'buyer_fee_total'=>round($buyerFeeTotal,2),'handling_fee'=>$handling,'tax'=>(float)$tax['amount'],'tax_snapshot'=>$tax['snapshot'],'total'=>round($subtotal+$buyerFeeTotal+$handling+((['snapshot']['inclusive']??false)?0:(float)$tax['amount']),2)];
 }
 public function createFromCart(int $userId,string $provider,string $currency,MarketplaceSettings $settings):Order {
  return DB::transaction(function()use($userId,$provider,$currency,$settings){
   $cart=CartItem::where('user_id',$userId)->with(['product.category.licenses','productLicense.license'])->lockForUpdate()->get();
   if($cart->isEmpty())throw ValidationException::withMessages(['cart'=>['Your cart is empty.']]);
   $itemSubtotal=0.0;$buyerFeeTotal=0.0;$lines=[];
   foreach($cart as $entry){
    if($entry->product?->status!=='published'||!$entry->productLicense)throw ValidationException::withMessages(['cart'=>['A cart item is no longer available.']]);
    if($entry->product?->author_id === $userId)throw ValidationException::withMessages(['cart'=>['Authors cannot purchase their own products. Remove the item to continue.']]);
    $price=round((float)$entry->productLicense->price,2);$category=$entry->product->category?->pricingCategory();
    $licenseConfig=$category?->licenses?->firstWhere('id',$entry->productLicense->license_id);
    if(!$licenseConfig)throw ValidationException::withMessages(['cart'=>['This license is no longer available for its category. Please update your cart.']]);
    $feeValue=(float)($licenseConfig->pivot->buyer_fee_value??0);
    $unitBuyerFee=round(($licenseConfig->pivot->buyer_fee_type??'fixed')==='percent'?$price*$feeValue/100:$feeValue,2);
    $quantity=max(1,(int)$entry->quantity);$itemSubtotal+=$price*$quantity;$buyerFeeTotal+=$unitBuyerFee*$quantity;$lines[]=compact('entry','price','unitBuyerFee','quantity');
   }
   $listTotal=round($itemSubtotal+$buyerFeeTotal,2);$handling=$this->handlingFee($listTotal,$settings->group('billing'));
   $tax=app(TaxCalculationService::class)->calculate(number_format($listTotal+$handling,2,'.',''),$currency);$order=Order::create(['public_id'=>(string)Str::uuid(),'user_id'=>$userId,'provider'=>$provider,'status'=>'pending','currency'=>$currency,'subtotal'=>round($itemSubtotal,2),'discount_total'=>0,'buyer_fee_total'=>round($buyerFeeTotal,2),'tax'=>$tax['amount'],'tax_snapshot'=>$tax['snapshot'],'handling_fee'=>$handling,'total'=>($tax['snapshot']['inclusive']??false)?number_format($listTotal+$handling,2,'.',''):bcadd(number_format($listTotal+$handling,2,'.',''),$tax['amount'],2)]);
   foreach($lines as $line){$entry=$line['entry'];OrderItem::create(['order_id'=>$order->id,'product_id'=>$entry->product_id,'product_license_id'=>$entry->product_license_id,'product_name'=>$entry->product->name,'license_name'=>$entry->productLicense->license?->name??'License','quantity'=>$line['quantity'],'unit_price'=>$line['price'],'unit_buyer_fee'=>$line['unitBuyerFee'],'buyer_fee_total'=>round($line['unitBuyerFee']*$line['quantity'],2),'total'=>round(($line['price']+$line['unitBuyerFee'])*$line['quantity'],2)]);}
   return $order->load('items');
  });
 }
 private function handlingFee(float $listTotal,array $billing):float {if(($billing['handling_fee_enabled']??true)===false)return 0.0;$low=max(0,(float)($billing['handling_fee_low_threshold']??10));$mid=max($low,(float)($billing['handling_fee_mid_threshold']??150));if($listTotal<$low)return round(max(0,(float)($billing['handling_fee_low_amount']??1)),2);if($listTotal<$mid)return round(max(0,(float)($billing['handling_fee_mid_amount']??3)),2);return 0.0;}
}
