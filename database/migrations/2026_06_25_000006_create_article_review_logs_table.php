<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('article_review_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('article_id')->constrained()->cascadeOnDelete();
            $table->foreignId('reviewer_id')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();
            $table->enum('action', [
                'submit',
                'auto_reject',
                'approve',
                'reject',
                'revision_required',
                'appeal',
                'second_approve',
                'second_reject',
                'publish',
                'unlist',
            ]);
            $table->text('reason')->nullable();
            $table->integer('review_round')->default(1);
            $table->json('sensitive_word_hit')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('article_id');
            $table->index('reviewer_id');
            $table->index('action');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('article_review_logs');
    }
};
