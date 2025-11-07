<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Add columns to approval_item_steps for dynamic step tracking
        Schema::table('approval_item_steps', function (Blueprint $table) {
            // Flag untuk step yang bisa insert step baru
            $table->boolean('can_insert_step')->default(false)->after('status');
            
            // Pre-configured insert step template (JSON)
            $table->json('insert_step_template')->nullable()->after('can_insert_step')
                ->comment('Pre-configured step template for quick insertion');
            
            // Tracking untuk dynamic steps
            $table->boolean('is_dynamic')->default(false)->after('insert_step_template');
            $table->unsignedBigInteger('inserted_by')->nullable()->after('is_dynamic');
            $table->timestamp('inserted_at')->nullable()->after('inserted_by');
            $table->text('insertion_reason')->nullable()->after('inserted_at');
            $table->string('required_action', 100)->nullable()->after('insertion_reason')
                ->comment('Action required: upload_document, price_verification, etc.');
            
            // Foreign key
            $table->foreign('inserted_by')->references('id')->on('users')->onDelete('set null');
            
            // Indexes for performance
            $table->index(['can_insert_step', 'status']);
            $table->index(['is_dynamic']);
        });
        
        // 2. Update approval_workflows table to support step-level configuration
        // Note: workflow_steps is JSON, we'll add 'can_insert_step' field in JSON structure
        // No schema change needed, just documentation
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('approval_item_steps', function (Blueprint $table) {
            $table->dropForeign(['inserted_by']);
            $table->dropIndex(['can_insert_step', 'status']);
            $table->dropIndex(['is_dynamic']);
            
            $table->dropColumn([
                'can_insert_step',
                'insert_step_template',
                'is_dynamic',
                'inserted_by',
                'inserted_at',
                'insertion_reason',
                'required_action',
            ]);
        });
    }
};
