<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('articles', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('user_id');
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->ulid('category_id')
                  ->nullable();
            $table->foreign('category_id')->references('id')->on('article_categories')->nullOnDelete();
            $table->enum('status', [
                'draft',
                'pending',
                'first_pass',
                'published',
                'first_reject',
                'modify_required',
                'appealing',
                'second_pass',
                'second_reject',
                'unlisted',
            ])->default('draft');
            $table->string('title', 255);
            $table->string('slug', 300)->unique();
            $table->longText('content');
            $table->string('cover_image', 500)->nullable();
            $table->boolean('is_top')->default(false);
            $table->boolean('is_featured')->default(false);
            $table->decimal('price_gold', 10, 2)->default(0);
            $table->bigInteger('view_count')->default(0);
            $table->integer('review_count')->default(0);
            $table->timestamp('last_review_at')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index('category_id');
            $table->index('status');
            $table->index('published_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('articles');
    }
};
