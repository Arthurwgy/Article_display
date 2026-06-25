<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('article_snapshots', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('article_id');
            $table->integer('review_round');
            $table->string('title_snapshot', 255);
            $table->longText('content_snapshot');
            $table->string('cover_image_snapshot', 500)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('article_id')->references('id')->on('articles')->cascadeOnDelete();
            $table->unique(['article_id', 'review_round']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('article_snapshots');
    }
};
