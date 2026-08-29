<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class PaymentEvent extends Model {
 protected $fillable=['provider','event_id','order_id','event_type','payload','processed_at'];
 protected $casts=['payload'=>'array','processed_at'=>'datetime'];
 public function order(){return $this->belongsTo(Order::class);}
}