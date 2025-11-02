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
        // Drop the old request-level approval steps table (replaced by approval_item_steps)
        Schema::dropIfExists('approval_steps');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Cannot rollback - approval flow has fundamentally changed from request-level to item-level
        // If you need to restore, use database backup
        throw new Exception('Cannot rollback this migration. The old request-level approval system is incompatible with the new per-item approval system. Restore from backup if needed.');
    }
};
