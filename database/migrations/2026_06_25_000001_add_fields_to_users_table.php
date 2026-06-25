<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['reader', 'author', 'admin'])
                  ->default('reader')
                  ->after('password');
            $table->string('avatar', 500)->nullable()->after('role');
            $table->text('bio')->nullable()->after('avatar');
            $table->timestamp('last_login_at')->nullable()->after('bio');
            $table->date('last_coin_at')->nullable()->after('last_login_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'role',
                'avatar',
                'bio',
                'last_login_at',
                'last_coin_at',
            ]);
        });
    }
};
