<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('article_tag', function (Blueprint $table) {
            $table->ulid('article_id');
            $table->unsignedBigInteger('tag_id');
            $table->timestamp('created_at')->useCurrent();

            $table->primary(['article_id', 'tag_id']);
            $table->foreign('article_id')->references('id')->on('articles')->cascadeOnDelete();
            $table->foreign('tag_id')->references('id')->on('article_tags')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('article_tag');
    }
};
