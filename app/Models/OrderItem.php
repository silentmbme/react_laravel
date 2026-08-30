<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class OrderItem extends Model {
 protected $fillable=['order_id','product_id','product_license_id','product_name','license_name','quantity','unit_price','unit_buyer_fee','buyer_fee_total','total'];
 protected $casts=['unit_price'=>'decimal:2','unit_buyer_fee'=>'decimal:2','buyer_fee_total'=>'decimal:2','total'=>'decimal:2'];
 public function order(){return $this->belongsTo(Order::class);}
 public function product(){return $this->belongsTo(Product::class);}
 public function entitlement(){return $this->hasOne(DownloadEntitlement::class)->orderBy('unit_number');}
 public function entitlements(){return $this->hasMany(DownloadEntitlement::class)->orderBy('unit_number');}
 public function purchaseVerification(){return $this->hasOne(PurchaseVerification::class)->orderBy('unit_number');}
 public function purchaseVerifications(){return $this->hasMany(PurchaseVerification::class)->orderBy('unit_number');}
}