<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Extend enum to include 'requester_department_manager'
        DB::statement("ALTER TABLE `approval_steps` MODIFY `approver_type` ENUM('user','role','department_manager','department_level','requester_department_manager') NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Warning: This will fail if there are rows using 'requester_department_manager'.
        // We will first map any such rows back to 'department_manager' to allow schema rollback.
        DB::statement("UPDATE `approval_steps` SET `approver_type` = 'department_manager' WHERE `approver_type` = 'requester_department_manager'");
        DB::statement("ALTER TABLE `approval_steps` MODIFY `approver_type` ENUM('user','role','department_manager','department_level') NOT NULL");
    }
};
