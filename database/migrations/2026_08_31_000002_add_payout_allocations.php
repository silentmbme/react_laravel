<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
 public function up(): void { Schema::create('payout_items', function (Blueprint $table) {$table->id();$table->foreignId('payout_id')->constrained()->restrictOnDelete();$table->foreignId('financial_transaction_id')->unique()->constrained()->restrictOnDelete();$table->decimal('amount',14,2);$table->char('currency',3)->default('USD');$table->timestamps();$table->index(['payout_id','currency']);}); }
 public function down(): void { Schema::dropIfExists('payout_items'); }
};
