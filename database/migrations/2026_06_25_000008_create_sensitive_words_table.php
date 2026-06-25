<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sensitive_words', function (Blueprint $table) {
            $table->id();
            $table->string('word', 100);
            $table->enum('level', ['light', 'moderate', 'severe']);
            $table->string('group_name', 50)->nullable();
            $table->timestamps();

            $table->unique('word');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sensitive_words');
    }
};
