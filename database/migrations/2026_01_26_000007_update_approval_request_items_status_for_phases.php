<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Add new status values for 3-phase workflow:
     * - in_purchasing: Item approved, waiting for vendor selection
     * - in_release: Item in release phase (after purchasing complete)
     */
    public function up(): void
    {
        // Update status enum to include new phases
        // MySQL ALTER ENUM approach
        \DB::statement("ALTER TABLE approval_request_items MODIFY COLUMN status ENUM('pending', 'on progress', 'in_purchasing', 'in_release', 'approved', 'rejected', 'cancelled') DEFAULT 'pending'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert to original status values
        \DB::statement("ALTER TABLE approval_request_items MODIFY COLUMN status ENUM('pending', 'on progress', 'approved', 'rejected', 'cancelled') DEFAULT 'pending'");
    }
};
