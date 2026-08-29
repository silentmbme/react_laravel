<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class DownloadEntitlement extends Model {
 protected $fillable=['user_id','order_item_id','unit_number','download_count','last_downloaded_at'];
 protected $casts=['last_downloaded_at'=>'datetime'];
 public function user(){return $this->belongsTo(User::class);}
 public function orderItem(){return $this->belongsTo(OrderItem::class);}
}