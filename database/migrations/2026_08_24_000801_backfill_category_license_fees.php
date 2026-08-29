<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
 public function up(): void {
   $licenseIds=DB::table('licenses')->where('status',true)->pluck('id');
   foreach(DB::table('categories')->get() as $category){foreach($licenseIds as $licenseId){DB::table('category_license')->insertOrIgnore(['category_id'=>$category->id,'license_id'=>$licenseId,'buyer_fee_type'=>$category->buyer_fee_type??'fixed','buyer_fee_value'=>$category->buyer_fee_value??0,'created_at'=>now(),'updated_at'=>now()]);}}
 }
 public function down(): void {}
};
