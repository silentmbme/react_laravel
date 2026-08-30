<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void {
  Schema::create('orders', function (Blueprint $table) {$table->id();$table->uuid('public_id')->unique();$table->foreignId('user_id')->constrained()->cascadeOnDelete();$table->string('provider')->nullable();$table->string('provider_reference')->nullable()->unique();$table->string('status')->default('pending')->index();$table->char('currency',3)->default('USD');$table->decimal('subtotal',12,2);$table->decimal('tax',12,2)->default(0);$table->decimal('total',12,2);$table->timestamp('paid_at')->nullable();$table->timestamps();});
  Schema::create('order_items', function (Blueprint $table) {$table->id();$table->foreignId('order_id')->constrained()->cascadeOnDelete();$table->foreignId('product_id')->constrained()->restrictOnDelete();$table->foreignId('product_license_id')->constrained()->restrictOnDelete();$table->string('product_name');$table->string('license_name');$table->unsignedInteger('quantity')->default(1);$table->decimal('unit_price',12,2);$table->decimal('total',12,2);$table->timestamps();});
  Schema::create('payment_events', function (Blueprint $table) {$table->id();$table->string('provider');$table->string('event_id');$table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();$table->string('event_type');$table->json('payload');$table->timestamp('processed_at')->nullable();$table->timestamps();$table->unique(['provider','event_id']);});
  Schema::create('download_entitlements', function (Blueprint $table) {$table->id();$table->foreignId('user_id')->constrained()->cascadeOnDelete();$table->foreignId('order_item_id')->constrained()->cascadeOnDelete();$table->unsignedInteger('download_count')->default(0);$table->timestamp('last_downloaded_at')->nullable();$table->timestamps();$table->unique(['user_id','order_item_id']);});
 }
 public function down(): void {Schema::dropIfExists('download_entitlements');Schema::dropIfExists('payment_events');Schema::dropIfExists('order_items');Schema::dropIfExists('orders');}
};