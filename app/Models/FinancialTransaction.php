<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class FinancialTransaction extends Model {
 protected $fillable=['transaction_id','order_id','order_item_id','customer_id','seller_id','product_id','type','status','amount','currency','exchange_rate','base_amount','base_currency','fee_amount','tax_amount','net_amount','payment_reference','idempotency_key','metadata'];
 protected $casts=['amount'=>'decimal:2','exchange_rate'=>'decimal:8','base_amount'=>'decimal:2','fee_amount'=>'decimal:2','tax_amount'=>'decimal:2','net_amount'=>'decimal:2','metadata'=>'array'];
 public function order(){return $this->belongsTo(Order::class);} public function entries(){return $this->hasMany(LedgerEntry::class);} public function payoutItem(){return $this->hasOne(PayoutItem::class);}
}
