<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1) Add standalone index on parent_id if not exists
        $hasIndex = DB::selectOne("
            SELECT 1 FROM pg_indexes
            WHERE tablename = 'departments'
            AND indexname = 'departments_parent_id_index'
        ");
        if (!$hasIndex) {
            Schema::table('departments', function (Blueprint $table) {
                $table->index('parent_id');
            });
        }

        // 2) Drop composite index if exists
        $hasComposite = DB::selectOne("
            SELECT 1 FROM pg_indexes
            WHERE tablename = 'departments'
            AND indexname = 'departments_parent_id_level_index'
        ");
        if ($hasComposite) {
            DB::statement('DROP INDEX departments_parent_id_level_index');
        }

        // 3) Drop columns if they exist
        Schema::table('departments', function (Blueprint $table) {
            $cols = [];
            if (Schema::hasColumn('departments', 'level')) $cols[] = 'level';
            if (Schema::hasColumn('departments', 'approval_level')) $cols[] = 'approval_level';
            if (!empty($cols)) $table->dropColumn($cols);
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

        $hasComposite = DB::selectOne("
            SELECT 1 FROM pg_indexes
            WHERE tablename = 'departments'
            AND indexname = 'departments_parent_id_level_index'
        ");
        if (!$hasComposite) {
            Schema::table('departments', function (Blueprint $table) {
                $table->index(['parent_id', 'level'], 'departments_parent_id_level_index');
            });
        }
    }
};
