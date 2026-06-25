<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('article_tags', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50);
            $table->string('slug', 80)->unique();
            $table->bigInteger('use_count')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('article_tags');
    }
};
