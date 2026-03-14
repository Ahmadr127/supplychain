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
        \DB::statement("ALTER TABLE approval_request_items DROP CONSTRAINT IF EXISTS approval_request_items_status_check");
        \DB::statement("ALTER TABLE approval_request_items ALTER COLUMN status TYPE VARCHAR(255)");
        \DB::statement("ALTER TABLE approval_request_items ADD CONSTRAINT approval_request_items_status_check CHECK (status IN ('pending', 'on progress', 'in_purchasing', 'in_release', 'approved', 'rejected', 'cancelled'))");
        \DB::statement("ALTER TABLE approval_request_items ALTER COLUMN status SET DEFAULT 'pending'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        \DB::statement("ALTER TABLE approval_request_items DROP CONSTRAINT IF EXISTS approval_request_items_status_check");
        \DB::statement("ALTER TABLE approval_request_items ADD CONSTRAINT approval_request_items_status_check CHECK (status IN ('pending', 'on progress', 'approved', 'rejected', 'cancelled'))");
    }
};
