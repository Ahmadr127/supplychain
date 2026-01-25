<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Update approval_workflows table untuk mendukung:
     * - Procurement Type (BARANG_BARU / PEREMAJAAN)
     * - Nominal Range (threshold min/max)
     */
    public function up(): void
    {
        Schema::table('approval_workflows', function (Blueprint $table) {
            // Procurement type reference
            $table->foreignId('procurement_type_id')->nullable()->after('item_type_id')
                  ->constrained('procurement_types')->nullOnDelete();
            
            // Nominal thresholds
            $table->decimal('nominal_min', 15, 2)->nullable()->after('procurement_type_id'); // Minimum nominal (inclusive)
            $table->decimal('nominal_max', 15, 2)->nullable()->after('nominal_min');         // Maximum nominal (exclusive)
            
            // Nominal range label for easier querying
            $table->enum('nominal_range', ['low', 'medium', 'high'])->nullable()->after('nominal_max');
            
            // Priority for workflow selection (higher = more specific, checked first)
            $table->integer('priority')->default(0)->after('nominal_range');
            
            // Add index for workflow selection queries
            $table->index(['procurement_type_id', 'nominal_range', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('approval_workflows', function (Blueprint $table) {
            $table->dropForeign(['procurement_type_id']);
            $table->dropIndex(['procurement_type_id', 'nominal_range', 'is_active']);
            $table->dropColumn(['procurement_type_id', 'nominal_min', 'nominal_max', 'nominal_range', 'priority']);
        });
    }
};
