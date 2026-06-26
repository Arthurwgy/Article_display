<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('article_categories', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('parent_id')->nullable();
            $table->string('name', 100);
            $table->string('slug', 150)->unique();
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->foreign('parent_id')->references('id')->on('article_categories')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('article_categories');
    }
};
