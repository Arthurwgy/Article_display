<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coin_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('type', [
                'login_daily',
                'article_published',
                'comment_liked',
                'article_received',
                'platform_grant',
            ]);
            $table->integer('amount');
            $table->foreignId('article_id')
                  ->nullable()
                  ->constrained()
                  ->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->index('user_id');
            $table->index('article_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coin_transactions');
    }
};
