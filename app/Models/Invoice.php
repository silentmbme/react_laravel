<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Invoice extends Model { protected $fillable=['order_id','number','snapshot','issued_at']; protected $casts=['snapshot'=>'array','issued_at'=>'datetime']; public function order(){return $this->belongsTo(Order::class);} }
