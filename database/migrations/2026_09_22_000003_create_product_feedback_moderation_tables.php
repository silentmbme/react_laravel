<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('product_feedback_replies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('feedback_type', 32);
            $table->unsignedBigInteger('feedback_id');
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('body');
            $table->timestamps();
            $table->index(['feedback_type', 'feedback_id']);
        });
        Schema::create('product_feedback_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('feedback_type', 32);
            $table->unsignedBigInteger('feedback_id');
            $table->foreignId('reporter_id')->constrained('users')->cascadeOnDelete();
            $table->string('reason', 500)->nullable();
            $table->string('status', 20)->default('open');
            $table->timestamps();
            $table->unique(['feedback_type', 'feedback_id', 'reporter_id'], 'feedback_report_unique');
            $table->index(['status', 'created_at']);
        });
    }
    public function down(): void {
        Schema::dropIfExists('product_feedback_reports');
        Schema::dropIfExists('product_feedback_replies');
    }
};
