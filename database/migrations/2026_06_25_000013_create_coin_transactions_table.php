<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coin_transactions', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('user_id');
            $table->enum('type', [
                'login_daily',
                'article_published',
                'comment_liked',
                'article_received',
                'platform_grant',
            ]);
            $table->integer('amount');
            $table->ulid('article_id')
                  ->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('article_id')->references('id')->on('articles')->nullOnDelete();

            $table->index('user_id');
            $table->index('article_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coin_transactions');
    }
};
