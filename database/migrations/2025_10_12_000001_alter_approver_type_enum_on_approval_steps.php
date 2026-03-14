<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE approval_steps DROP CONSTRAINT IF EXISTS approval_steps_approver_type_check");
        DB::statement("ALTER TABLE approval_steps ALTER COLUMN approver_type TYPE VARCHAR(255)");
        DB::statement("ALTER TABLE approval_steps ADD CONSTRAINT approval_steps_approver_type_check CHECK (approver_type IN ('user','role','department_manager','department_level','requester_department_manager'))");
        DB::statement("ALTER TABLE approval_steps ALTER COLUMN approver_type SET NOT NULL");
    }

    public function down(): void
    {
        DB::statement("UPDATE approval_steps SET approver_type = 'department_manager' WHERE approver_type = 'requester_department_manager'");
        DB::statement("ALTER TABLE approval_steps DROP CONSTRAINT IF EXISTS approval_steps_approver_type_check");
        DB::statement("ALTER TABLE approval_steps ADD CONSTRAINT approval_steps_approver_type_check CHECK (approver_type IN ('user','role','department_manager','department_level'))");
    }
};
