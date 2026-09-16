<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Payout extends Model { protected $fillable=['payout_id','seller_id','amount','currency','status','payout_method','external_reference','requested_at','processed_at','failure_reason']; protected $casts=['amount'=>'decimal:2','requested_at'=>'datetime','processed_at'=>'datetime']; public function seller(){return $this->belongsTo(User::class,'seller_id');} public function items(){return $this->hasMany(PayoutItem::class);} }
