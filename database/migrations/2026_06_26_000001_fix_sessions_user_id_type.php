<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Fix sessions.user_id to match users.id ULID (26 chars).
     *
     * Root cause: stock Laravel migration used `$table->foreignId('user_id')`
     * (bigint unsigned) but this project switched users.id to ULID.
     * Login fails with MySQL 1265 "Data truncated".
     */
    public function up(): void
    {
        Schema::table('sessions', function (Blueprint $table) {
            $table->dropIndex('sessions_user_id_index');
        });

        \Illuminate\Support\Facades\DB::statement('ALTER TABLE sessions MODIFY COLUMN user_id CHAR(26) NULL');

        Schema::table('sessions', function (Blueprint $table) {
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::table('sessions', function (Blueprint $table) {
            $table->dropIndex('sessions_user_id_index');
        });

        \Illuminate\Support\Facades\DB::statement('ALTER TABLE sessions MODIFY COLUMN user_id BIGINT UNSIGNED NULL');

        Schema::table('sessions', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->change();
        });
    }
};
