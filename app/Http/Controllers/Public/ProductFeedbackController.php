<?php
namespace App\Http\Controllers\Public;
use App\Http\Controllers\Controller;
use App\Models\CustomerProductReview;
use App\Models\Product;
use App\Models\ProductComment;
use App\Models\ProductFeedbackReply;
use App\Models\ProductFeedbackReport;
use Illuminate\Http\Request;

class ProductFeedbackController extends Controller {
 private function feedback(Product $product,string $type,int $id){
  $class=$type==='review'?CustomerProductReview::class:($type==='comment'?ProductComment::class:null);
  abort_unless($class,422,'Unknown feedback type.');
  $feedback=$class::findOrFail($id);
  abort_unless((int)$feedback->product_id===(int)$product->id,404);
  return $feedback;
 }
 public function reply(Request $request,Product $product){
  $user=$request->user();abort_unless($user&&(int)$user->id===(int)$product->author_id,403,'Only the product author can reply.');
  $data=$request->validate(['type'=>['required','in:review,comment'],'feedback_id'=>['required','integer'],'body'=>['required','string','max:2000']]);
  $this->feedback($product,$data['type'],(int)$data['feedback_id']);
  return response()->json(['reply'=>ProductFeedbackReply::create(['product_id'=>$product->id,'feedback_type'=>$data['type'],'feedback_id'=>$data['feedback_id'],'user_id'=>$user->id,'body'=>$data['body']])->load('user:id,name')],201);
 }
 public function report(Request $request,Product $product){
  $user=$request->user();abort_unless($user&&(int)$user->id===(int)$product->author_id,403,'Only the product author can request feedback removal.');$data=$request->validate(['type'=>['required','in:review,comment'],'feedback_id'=>['required','integer'],'reason'=>['nullable','string','max:500']]);
  $feedback=$this->feedback($product,$data['type'],(int)$data['feedback_id']);abort_unless((int)$feedback->user_id!==(int)$user->id,422,'You cannot request removal of your own feedback.');
  ProductFeedbackReport::updateOrCreate(['feedback_type'=>$data['type'],'feedback_id'=>$data['feedback_id'],'reporter_id'=>$user->id],['product_id'=>$product->id,'reason'=>$data['reason']??null,'status'=>'open']);
  return response()->json(['message'=>'Your removal request has been sent to the marketplace team.']);
 }
}
