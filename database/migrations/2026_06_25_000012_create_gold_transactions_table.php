<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gold_transactions', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('from_user_id')
                  ->nullable();
            $table->ulid('to_user_id')
                  ->nullable();
            $table->enum('type', [
                'recharge',
                'reward',
                'read',
                'refund',
                'payout',
            ]);
            $table->decimal('amount', 10, 2);
            $table->ulid('article_id')
                  ->nullable();
            $table->string('order_no', 64)->unique();
            $table->json('split_data')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('from_user_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('to_user_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('article_id')->references('id')->on('articles')->nullOnDelete();

            $table->index('from_user_id');
            $table->index('to_user_id');
            $table->index('article_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gold_transactions');
    }
};
