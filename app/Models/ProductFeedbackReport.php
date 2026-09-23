<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class ProductFeedbackReport extends Model {
 protected $fillable=['product_id','feedback_type','feedback_id','reporter_id','reason','status'];
}
