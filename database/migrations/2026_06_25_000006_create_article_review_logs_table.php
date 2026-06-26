<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('article_review_logs', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('article_id');
            $table->ulid('reviewer_id')->nullable();
            $table->enum('action', [
                'submit',
                'auto_reject',
                'first_pass',
                'first_reject',
                'modify_required',
                'appeal',
                'second_pass',
                'second_reject',
            ]);
            $table->text('reason')->nullable();
            $table->integer('review_round')->default(1);
            $table->json('sensitive_word_hit')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('article_id')->references('id')->on('articles')->cascadeOnDelete();
            $table->foreign('reviewer_id')->references('id')->on('users')->nullOnDelete();
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
