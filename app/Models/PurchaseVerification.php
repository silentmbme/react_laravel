<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;use Illuminate\Support\Facades\Crypt;
class PurchaseVerification extends Model {
 protected $fillable=['order_item_id','unit_number','code_hash','code_encrypted','support_until'];
 protected $hidden=['code_hash','code_encrypted'];
 protected $casts=['support_until'=>'datetime'];
 public function orderItem(){return $this->belongsTo(OrderItem::class);}
 public function purchaseCode():string{return Crypt::decryptString($this->code_encrypted);}
}