<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void { Schema::table('category_license', function(Blueprint $table) { $table->string('buyer_fee_type',10)->default('fixed')->after('license_id'); $table->decimal('buyer_fee_value',12,2)->default(0)->after('buyer_fee_type'); }); }
 public function down(): void { Schema::table('category_license', function(Blueprint $table) { $table->dropColumn(['buyer_fee_type','buyer_fee_value']); }); }
};
