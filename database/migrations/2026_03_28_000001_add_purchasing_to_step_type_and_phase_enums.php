<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Add 'purchasing' to step_type and step_phase enums
     * in approval_item_steps table.
     * 
     * Needed because DynamicWorkflowSeeder now creates purchasing
     * phase steps (6 steps: Terima Dok → Benchmarking → Vendor → PO → Invoice → Done)
     */
    public function up(): void
    {
        // --- step_type: add 'purchasing' ---
        DB::statement("ALTER TABLE approval_item_steps DROP CONSTRAINT IF EXISTS approval_item_steps_step_type_check");
        DB::statement("ALTER TABLE approval_item_steps ALTER COLUMN step_type TYPE VARCHAR(255)");
        DB::statement("ALTER TABLE approval_item_steps ADD CONSTRAINT approval_item_steps_step_type_check
            CHECK (step_type IN ('maker', 'approver', 'purchasing', 'releaser'))");

        // --- step_phase: add 'purchasing' ---
        DB::statement("ALTER TABLE approval_item_steps DROP CONSTRAINT IF EXISTS approval_item_steps_step_phase_check");
        DB::statement("ALTER TABLE approval_item_steps ALTER COLUMN step_phase TYPE VARCHAR(255)");
        DB::statement("ALTER TABLE approval_item_steps ADD CONSTRAINT approval_item_steps_step_phase_check
            CHECK (step_phase IN ('approval', 'purchasing', 'release'))");
    }

    public function down(): void
    {
        // Revert to original values (remove 'purchasing' rows first to avoid constraint failure)
        DB::statement("UPDATE approval_item_steps SET step_type = 'approver' WHERE step_type = 'purchasing'");
        DB::statement("UPDATE approval_item_steps SET step_phase = 'approval' WHERE step_phase = 'purchasing'");

        DB::statement("ALTER TABLE approval_item_steps DROP CONSTRAINT IF EXISTS approval_item_steps_step_type_check");
        DB::statement("ALTER TABLE approval_item_steps ADD CONSTRAINT approval_item_steps_step_type_check
            CHECK (step_type IN ('maker', 'approver', 'releaser'))");

        DB::statement("ALTER TABLE approval_item_steps DROP CONSTRAINT IF EXISTS approval_item_steps_step_phase_check");
        DB::statement("ALTER TABLE approval_item_steps ADD CONSTRAINT approval_item_steps_step_phase_check
            CHECK (step_phase IN ('approval', 'release'))");
    }
};
