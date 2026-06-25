<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('article_music', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('article_id');
            $table->foreign('article_id')->references('id')->on('articles')->cascadeOnDelete();
            $table->string('cover_url', 500);
            $table->string('audio_url', 500);
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('article_music');
    }
};
