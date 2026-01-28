<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Add 'steps' column to approval_workflows table.
     * This is used by DynamicWorkflowSeeder to store workflow step definitions as JSON.
     */
    public function up(): void
    {
        Schema::table('approval_workflows', function (Blueprint $table) {
            // Add steps column as JSON for storing workflow step definitions
            // This is an alias/alternative to workflow_steps column
            if (!Schema::hasColumn('approval_workflows', 'steps')) {
                $table->json('steps')->nullable()->after('workflow_steps');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('approval_workflows', function (Blueprint $table) {
            if (Schema::hasColumn('approval_workflows', 'steps')) {
                $table->dropColumn('steps');
            }
        });
    }
};
