<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Update approval_item_steps table untuk mendukung:
     * - Step Type (maker, approver, procurement, releaser)
     * - Scope Process (deskripsi tugas per step)
     * - CapEx selection tracking
     */
    public function up(): void
    {
        Schema::table('approval_item_steps', function (Blueprint $table) {
            // Step type classification (only 3 types in approval workflow)
            // Note: Procurement/SPH is part of PurchasingItem flow, not approval steps
            $table->enum('step_type', ['maker', 'approver', 'releaser'])
                  ->default('approver')->after('step_name');
            
            // Step phase: determines when step is active
            // - approval: runs before purchasing (Maker + Approvers)
            // - release: runs after purchasing complete (Releasers)
            $table->enum('step_phase', ['approval', 'release'])
                  ->default('approval')->after('step_type');
            
            // Scope process description (e.g., "Pemilihan ID Number CapEx", "Pembuatan FS")
            $table->string('scope_process', 255)->nullable()->after('step_phase');
            
            // CapEx selection (for Approver 1 - Manager Unit)
            $table->foreignId('selected_capex_id')->nullable()->after('scope_process')
                  ->constrained('capex_id_numbers')->nullOnDelete();
            
            // Skip tracking (for conditional steps)
            $table->text('skip_reason')->nullable()->after('comments');
            $table->timestamp('skipped_at')->nullable()->after('skip_reason');
            $table->foreignId('skipped_by')->nullable()->after('skipped_at')
                  ->constrained('users')->nullOnDelete();
            
            // Add index for step type filtering
            $table->index('step_type');
        });
        
        // Update status enum to include 'skipped' and 'pending_purchase' (for release phase waiting)
        // Note: MySQL ALTER ENUM is tricky, using raw SQL
        \DB::statement("ALTER TABLE approval_item_steps MODIFY COLUMN status ENUM('pending', 'pending_purchase', 'approved', 'rejected', 'skipped') DEFAULT 'pending'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert status enum first (remove pending_purchase and skipped)
        \DB::statement("ALTER TABLE approval_item_steps MODIFY COLUMN status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending'");
        
        Schema::table('approval_item_steps', function (Blueprint $table) {
            $table->dropForeign(['selected_capex_id']);
            $table->dropForeign(['skipped_by']);
            $table->dropIndex(['step_type']);
            $table->dropColumn([
                'step_type',
                'step_phase', 
                'scope_process', 
                'selected_capex_id', 
                'skip_reason', 
                'skipped_at', 
                'skipped_by'
            ]);
        });
    }
};
