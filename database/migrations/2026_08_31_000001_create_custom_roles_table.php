<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
 public function up(): void { Schema::create('custom_roles', function (Blueprint $table) {$table->id();$table->string('name');$table->string('slug')->unique();$table->boolean('is_staff')->default(true)->index();$table->json('permissions');$table->boolean('is_active')->default(true)->index();$table->timestamps();}); }
 public function down(): void { Schema::dropIfExists('custom_roles'); }
};
