<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up(): void
    {
        // Migrate existing rows using deprecated type 'department_level' to a safe type
        if (Schema::hasTable('approval_steps')) {
            DB::statement("UPDATE `approval_steps` SET `approver_type` = 'requester_department_manager' WHERE `approver_type` = 'department_level'");
        }

        // Update enum to add any_department_manager and remove department_level
        DB::statement("ALTER TABLE `approval_steps` MODIFY `approver_type` ENUM('user','role','department_manager','requester_department_manager','any_department_manager') NOT NULL");

        // Drop approver_level column if exists
        if (Schema::hasColumn('approval_steps', 'approver_level')) {
            Schema::table('approval_steps', function (Blueprint $table) {
                $table->dropColumn('approver_level');
            });
        }
    }

    public function down(): void
    {
        // Re-add approver_level column
        if (!Schema::hasColumn('approval_steps', 'approver_level')) {
            Schema::table('approval_steps', function (Blueprint $table) {
                $table->integer('approver_level')->nullable()->after('approver_department_id');
            });
        }
        // Restore enum back to include department_level and remove any_department_manager
        DB::statement("ALTER TABLE `approval_steps` MODIFY `approver_type` ENUM('user','role','department_manager','department_level','requester_department_manager') NOT NULL");

        // It's not feasible to restore data precisely for department_level; leave as requester_department_manager
    }
};
