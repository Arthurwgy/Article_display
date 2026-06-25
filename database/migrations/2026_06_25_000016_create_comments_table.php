<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comments', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('article_id');
            $table->ulid('user_id');
            $table->ulid('parent_id')->nullable();
            $table->text('content');
            $table->enum('status', [
                'pending',
                'published',
                'hidden',
                'deleted',
            ])->default('pending');
            $table->bigInteger('like_count')->default(0);
            $table->timestamps();

            $table->foreign('article_id')->references('id')->on('articles')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('parent_id')->references('id')->on('comments')->cascadeOnDelete();

            $table->index('article_id');
            $table->index('user_id');
            $table->index(['article_id', 'parent_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comments');
    }
};
