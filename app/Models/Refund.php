<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Refund extends Model { protected $fillable=['refund_id','order_id','order_item_id','requested_by','amount','currency','status','external_reference','idempotency_key','reason','metadata','processed_at']; protected $casts=['amount'=>'decimal:2','metadata'=>'array','processed_at'=>'datetime']; public function order(){return $this->belongsTo(Order::class);} }
