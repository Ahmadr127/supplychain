<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Update approval_requests table untuk mendukung:
     * - Procurement Type (link ke jenis pengadaan)
     * - CapEx ID Number (link ke capex yang dialokasikan)
     * - Total Amount (untuk workflow selection)
     */
    public function up(): void
    {
        Schema::table('approval_requests', function (Blueprint $table) {
            // Procurement type reference
            $table->foreignId('procurement_type_id')->nullable()->after('item_type_id')
                  ->constrained('procurement_types')->nullOnDelete();
            
            // CapEx ID Number reference (set saat Manager Unit approve)
            $table->foreignId('capex_id_number_id')->nullable()->after('procurement_type_id')
                  ->constrained('capex_id_numbers')->nullOnDelete();
            
            // Cached total amount for workflow decisions
            $table->decimal('total_amount', 15, 2)->nullable()->after('capex_id_number_id');
            
            // Add indexes
            $table->index('procurement_type_id');
            $table->index('capex_id_number_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('approval_requests', function (Blueprint $table) {
            $table->dropForeign(['procurement_type_id']);
            $table->dropForeign(['capex_id_number_id']);
            $table->dropIndex(['procurement_type_id']);
            $table->dropIndex(['capex_id_number_id']);
            $table->dropColumn(['procurement_type_id', 'capex_id_number_id', 'total_amount']);
        });
    }
};
