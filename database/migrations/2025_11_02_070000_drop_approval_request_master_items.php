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
        // Drop the old pivot table (replaced by approval_request_items)
        Schema::dropIfExists('approval_request_master_items');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Cannot rollback - data structure has fundamentally changed
        // If you need to restore, use database backup
        throw new Exception('Cannot rollback this migration. The old pivot table structure is incompatible with the new per-item approval system. Restore from backup if needed.');
    }
};
