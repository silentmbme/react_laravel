<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class License extends Model {
 protected $fillable=['name','slug','status','sort_order'];
 protected $casts=['status'=>'boolean','sort_order'=>'integer'];
 public function products(){return $this->hasMany(ProductLicense::class);}
 public function categories(){return $this->belongsToMany(Category::class)->withPivot(['buyer_fee_type','buyer_fee_value'])->withTimestamps();}
}
