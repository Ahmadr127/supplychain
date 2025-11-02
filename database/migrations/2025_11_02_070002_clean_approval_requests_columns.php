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
        Schema::table('approval_requests', function (Blueprint $table) {
            // Remove columns that are no longer used in per-item approval system
            // These columns were used for request-level approval tracking
            
            // Check if columns exist before dropping (in case they were already removed)
            if (Schema::hasColumn('approval_requests', 'current_step')) {
                $table->dropColumn('current_step');
            }
            
            if (Schema::hasColumn('approval_requests', 'total_steps')) {
                $table->dropColumn('total_steps');
            }
            
            // Note: We keep 'status', 'approved_by', 'approved_at' columns
            // because they are now aggregated from item statuses
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('approval_requests', function (Blueprint $table) {
            // Restore the columns if needed
            $table->integer('current_step')->nullable()->after('status');
            $table->integer('total_steps')->nullable()->after('current_step');
        });
    }
};
