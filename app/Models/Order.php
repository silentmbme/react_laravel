<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Order extends Model {
 protected $fillable=['public_id','user_id','provider','provider_reference','status','currency','subtotal','discount_total','buyer_fee_total','tax','tax_snapshot','handling_fee','total','refunded_total','paid_at'];
 protected $casts=['subtotal'=>'decimal:2','discount_total'=>'decimal:2','buyer_fee_total'=>'decimal:2','tax'=>'decimal:2','tax_snapshot'=>'array','handling_fee'=>'decimal:2','total'=>'decimal:2','refunded_total'=>'decimal:2','paid_at'=>'datetime'];
 public function user(){return $this->belongsTo(User::class);}
 public function items(){return $this->hasMany(OrderItem::class);}
 public function financialTransactions(){return $this->hasMany(FinancialTransaction::class);}
 public function refunds(){return $this->hasMany(Refund::class);}
 public function invoice(){return $this->hasOne(Invoice::class);}
}