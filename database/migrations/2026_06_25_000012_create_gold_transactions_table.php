<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gold_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('from_user_id')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();
            $table->foreignId('to_user_id')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();
            $table->enum('type', [
                'recharge',
                'reward',
                'read',
                'refund',
                'payout',
            ]);
            $table->decimal('amount', 10, 2);
            $table->foreignId('article_id')
                  ->nullable()
                  ->constrained()
                  ->nullOnDelete();
            $table->string('order_no', 64)->unique();
            $table->json('split_data')->nullable();
            $table->timestamp('created_at')->useCurrent();

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
