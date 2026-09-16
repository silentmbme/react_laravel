<?php
namespace App\Services;
use App\Models\Invoice;
use App\Models\Order;

class InvoiceService {
 public function issue(Order $order): Invoice {
  return Invoice::firstOrCreate(['order_id'=>$order->id], ['number'=>'INV-'.str_pad((string)$order->id, 10, '0', STR_PAD_LEFT),'issued_at'=>$order->paid_at ?? now(),'snapshot'=>['order_id'=>$order->public_id,'currency'=>$order->currency,'subtotal'=>(string)$order->subtotal,'discount_total'=>(string)$order->discount_total,'buyer_fee_total'=>(string)$order->buyer_fee_total,'handling_fee'=>(string)$order->handling_fee,'tax'=>(string)$order->tax,'tax_snapshot'=>$order->tax_snapshot,'total'=>(string)$order->total,'provider'=>$order->provider,'payment_reference'=>$order->provider_reference,'items'=>$order->items->map(fn($item)=>['product_name'=>$item->product_name,'license_name'=>$item->license_name,'quantity'=>$item->quantity,'unit_price'=>(string)$item->unit_price,'total'=>(string)$item->total])->values()->all()]]);
 }
}
