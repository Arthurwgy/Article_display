<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('article_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_id')
                  ->nullable()
                  ->constrained('comments')
                  ->cascadeOnDelete();
            $table->text('content');
            $table->enum('status', ['pending', 'published', 'hidden', 'deleted'])
                  ->default('pending');
            $table->bigInteger('like_count')->default(0);
            $table->timestamps();

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
