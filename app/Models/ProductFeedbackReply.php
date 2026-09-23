<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class ProductFeedbackReply extends Model {
 protected $fillable=['product_id','feedback_type','feedback_id','user_id','body'];
 public function user(){return $this->belongsTo(User::class);}
}
