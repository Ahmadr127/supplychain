<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1) Ensure parent_id has a standalone index BEFORE dropping composite to satisfy FK requirements
        Schema::table('departments', function (Blueprint $table) {
            try {
                $table->index('parent_id');
            } catch (\Throwable $e) {
                // ignore if already indexed
            }
        });

        // 2) Now safe to drop composite index if present and not needed
        // Some MySQL setups name composite index automatically; we try known name and fallback to dynamic check
        try {
            \Illuminate\Support\Facades\DB::statement('ALTER TABLE `departments` DROP INDEX `departments_parent_id_level_index`');
        } catch (\Throwable $e) {
            // ignore if drop fails (e.g., name differs or not exists)
        }

        // 3) Finally drop columns
        Schema::table('departments', function (Blueprint $table) {
            if (Schema::hasColumn('departments', 'level')) {
                $table->dropColumn('level');
            }
            if (Schema::hasColumn('departments', 'approval_level')) {
                $table->dropColumn('approval_level');
            }
        });
    }

    public function down(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            if (!Schema::hasColumn('departments', 'level')) {
                $table->integer('level')->default(1);
            }
            if (!Schema::hasColumn('departments', 'approval_level')) {
                $table->integer('approval_level')->default(1);
            }
        });

        // Recreate composite index
        Schema::table('departments', function (Blueprint $table) {
            try {
                $table->index(['parent_id', 'level'], 'departments_parent_id_level_index');
            } catch (\Throwable $e) {
                // ignore
            }
        });
    }
};
