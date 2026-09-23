<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class CustomerProductReview extends Model {
 protected $fillable=['product_id','user_id','rating','comment'];
 public function product(){return $this->belongsTo(Product::class);}
 public function user(){return $this->belongsTo(User::class);}
 public function replies(){return $this->hasMany(ProductFeedbackReply::class,'feedback_id')->where('feedback_type','review');}
}