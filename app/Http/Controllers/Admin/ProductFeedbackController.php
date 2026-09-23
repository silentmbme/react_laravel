<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\CustomerProductReview;
use App\Models\ProductComment;
use Illuminate\Http\Request;

class ProductFeedbackController extends Controller {
 public function destroy(Request $request,string $type,int $id){
  abort_unless($request->user()?->isStaff(),403);
  $model=$type==='review'?CustomerProductReview::class:($type==='comment'?ProductComment::class:null);abort_unless($model,422,'Unknown feedback type.');
  $feedback=$model::findOrFail($id);$product=$feedback->product;
  $feedback->delete();
  if($type==='review'){$stats=CustomerProductReview::where('product_id',$product->id)->selectRaw('AVG(rating) average_rating, COUNT(*) review_count')->first();$product->update(['rating'=>round((float)$stats->average_rating,2),'reviews_count'=>(int)$stats->review_count]);}
  return response()->json(['message'=>'Feedback removed.']);
 }
}
