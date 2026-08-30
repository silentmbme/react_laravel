<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Category extends Model {
 protected $fillable=['parent_id','name','slug','description','meta_title','meta_description','icon','image','sort_order','status','buyer_fee_type','buyer_fee_value'];
 protected $casts=['status'=>'boolean','buyer_fee_value'=>'decimal:2'];
 public function parent(){return $this->belongsTo(Category::class,'parent_id');}
 public function products(){return $this->hasMany(Product::class);}
 public function children(){return $this->hasMany(Category::class,'parent_id')->orderBy('sort_order');}
 public function licenses(){return $this->belongsToMany(License::class)->withPivot(['buyer_fee_type','buyer_fee_value'])->withTimestamps()->orderBy('sort_order');}
 public function pricingCategory(): self { $category=$this; while($category->parent_id){$category=$category->relationLoaded('parent')?$category->parent:$category->parent()->firstOrFail();} return $category; }
}
