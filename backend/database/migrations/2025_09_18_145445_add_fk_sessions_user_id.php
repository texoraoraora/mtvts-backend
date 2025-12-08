<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('sessions') && Schema::hasColumn('sessions', 'user_id')) {
            // Ensure correct type (unsigned big int, nullable)
            // If you don't have doctrine/dbal, comment change() and use the raw SQL below.
            try {
                Schema::table('sessions', function (Blueprint $table) {
                    $table->unsignedBigInteger('user_id')->nullable()->change();
                });
            } catch (\Throwable $e) {
                // Fallback without DBAL
                try {
                    // MySQL syntax
                    DB::statement('ALTER TABLE sessions MODIFY user_id BIGINT UNSIGNED NULL');
                } catch (\Throwable $ignored) {
                }
                try {
                    // Postgres syntax
                    DB::statement('ALTER TABLE sessions ALTER COLUMN user_id DROP NOT NULL');
                    DB::statement('ALTER TABLE sessions ALTER COLUMN user_id TYPE BIGINT USING user_id::bigint');
                } catch (\Throwable $ignored) {
                }
            }

            // Null out orphans so FK can be added
            try {
                // MySQL cleanup
                DB::statement("
                    UPDATE sessions s
                    LEFT JOIN users u ON u.id = s.user_id
                    SET s.user_id = NULL
                    WHERE s.user_id IS NOT NULL AND u.id IS NULL
                ");
            } catch (\Throwable $ignored) {
                // Postgres cleanup
               DB::statement("
        UPDATE sessions
        SET user_id = NULL
        WHERE user_id IS NOT NULL
        AND NOT EXISTS (
            SELECT 1 FROM users WHERE users.id = sessions.user_id
        )
    ");
            }

            // Drop existing FK if present (idempotent)
            try {
                Schema::table('sessions', fn(Blueprint $t) => $t->dropForeign(['user_id']));
            } catch (\Throwable $e) {
            }

            // ⚠️ Do NOT add a manual index; MySQL will add one automatically for the FK
           Schema::table('sessions', function (Blueprint $table) {
        $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
    });
}

    }

    public function down(): void
    {
        if (Schema::hasTable('sessions') && Schema::hasColumn('sessions', 'user_id')) {
            try {
                Schema::table('sessions', function (Blueprint $table) {
        $table->dropForeign(['user_id']);
    });
                catch (\Throwable $e) {
            }
            // No need to drop index explicitly; MySQL created it for the FK.
        }
    }
};
