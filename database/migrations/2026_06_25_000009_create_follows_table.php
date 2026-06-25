<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('follows', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('follower_id');
            $table->ulid('following_id');
            $table->boolean('is_special')->default(false);
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('follower_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('following_id')->references('id')->on('users')->cascadeOnDelete();
            $table->unique(['follower_id', 'following_id']);
            $table->index('follower_id');
            $table->index('following_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('follows');
    }
};
