<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {

            $table->id();

            $table->foreignId('author_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->foreignId('category_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('name');

            $table->string('slug')
                ->unique();

            $table->text('short_description');

            $table->longText('description');

            $table->string('thumbnail');

            $table->string('file');

            $table->unsignedBigInteger('file_size')
                ->nullable();

            $table->string('version')
                ->default('1.0.0');

            $table->string('demo_url')
                ->nullable();

            $table->enum('status', [
                'draft',
                'pending',
                'published',
                'rejected'
            ])->default('draft');

            $table->unsignedInteger('views')
                ->default(0);

            $table->unsignedInteger('sales')
                ->default(0);

            $table->decimal('rating', 3, 2)
                ->default(0);

            $table->unsignedInteger('reviews_count')
                ->default(0);

            $table->timestamps();

            $table->softDeletes();

            $table->index('author_id');
            $table->index('category_id');
            $table->index('status');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
