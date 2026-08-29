<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Order extends Model {
 protected $fillable=['public_id','user_id','provider','provider_reference','status','currency','subtotal','buyer_fee_total','tax','handling_fee','total','paid_at'];
 protected $casts=['subtotal'=>'decimal:2','buyer_fee_total'=>'decimal:2','tax'=>'decimal:2','handling_fee'=>'decimal:2','total'=>'decimal:2','paid_at'=>'datetime'];
 public function user(){return $this->belongsTo(User::class);}
 public function items(){return $this->hasMany(OrderItem::class);}
}
