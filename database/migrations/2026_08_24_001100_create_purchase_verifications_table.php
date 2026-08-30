<?php
use Illuminate\Database\Migrations\Migration;use Illuminate\Database\Schema\Blueprint;use Illuminate\Support\Facades\Schema;
return new class extends Migration {public function up():void{Schema::create('purchase_verifications',function(Blueprint $table){$table->id();$table->foreignId('order_item_id')->unique()->constrained()->cascadeOnDelete();$table->char('code_hash',64)->unique();$table->text('code_encrypted');$table->timestamp('support_until')->nullable();$table->timestamps();});}public function down():void{Schema::dropIfExists('purchase_verifications');}};
