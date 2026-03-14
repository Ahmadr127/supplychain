<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('approval_steps')) {
            DB::statement("UPDATE approval_steps SET approver_type = 'requester_department_manager' WHERE approver_type = 'department_level'");
        }

        DB::statement("ALTER TABLE approval_steps DROP CONSTRAINT IF EXISTS approval_steps_approver_type_check");
        DB::statement("ALTER TABLE approval_steps ALTER COLUMN approver_type TYPE VARCHAR(255)");
        DB::statement("ALTER TABLE approval_steps ADD CONSTRAINT approval_steps_approver_type_check CHECK (approver_type IN ('user','role','department_manager','requester_department_manager','any_department_manager'))");
        DB::statement("ALTER TABLE approval_steps ALTER COLUMN approver_type SET NOT NULL");

        if (Schema::hasColumn('approval_steps', 'approver_level')) {
            Schema::table('approval_steps', function (Blueprint $table) {
                $table->dropColumn('approver_level');
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasColumn('approval_steps', 'approver_level')) {
            Schema::table('approval_steps', function (Blueprint $table) {
                $table->integer('approver_level')->nullable()->after('approver_department_id');
            });
        }

        DB::statement("ALTER TABLE approval_steps DROP CONSTRAINT IF EXISTS approval_steps_approver_type_check");
        DB::statement("ALTER TABLE approval_steps ADD CONSTRAINT approval_steps_approver_type_check CHECK (approver_type IN ('user','role','department_manager','department_level','requester_department_manager'))");
    }
};
