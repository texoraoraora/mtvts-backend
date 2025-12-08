<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('sessions')) return;
        if (!Schema::hasColumn('sessions', 'user_id')) return;

        // Force user_id to be nullable (Postgres safe)
        DB::unprepared("ALTER TABLE sessions ALTER COLUMN user_id DROP NOT NULL");

        // Clean orphans (Postgres safe, no JOIN)
        DB::unprepared("
            UPDATE sessions
            SET user_id = NULL
            WHERE user_id IS NOT NULL
            AND NOT EXISTS (
                SELECT 1 FROM users WHERE users.id = sessions.user_id
            )
        ");

        // Drop FK if exists
        try {
            Schema::table('sessions', function (Blueprint $table) {
                $table->dropForeign(['user_id']);
            });
        } catch (\Throwable $e) { }

        // Add FK again
        Schema::table('sessions', function (Blueprint $table) {
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('sessions')) return;
        if (!Schema::hasColumn('sessions', 'user_id')) return;

        try {
            Schema::table('sessions', function (Blueprint $table) {
                $table->dropForeign(['user_id']);
            });
        } catch (\Throwable $e) {}
    }
};
