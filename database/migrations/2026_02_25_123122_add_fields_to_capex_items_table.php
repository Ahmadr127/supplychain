<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('capex_items', function (Blueprint $table) {
            // Excel: Skala Prioritas (1, 2, 3)
            $table->tinyInteger('priority_scale')->nullable()->after('item_name');

            // Excel: Bulan pengadaan (January-December)
            $table->string('month', 20)->nullable()->after('priority_scale');

            // Excel: Amount/thn (anggaran per tahun, bisa berbeda dari budget_amount total)
            $table->decimal('amount_per_year', 15, 2)->nullable()->after('month');

            // Excel: Kategori item (New / Replacement)
            $table->string('capex_type', 50)->nullable()->after('amount_per_year');

            // Excel: PIC (person in charge)
            $table->string('pic', 100)->nullable()->after('capex_type');
        });

        // Update capex_id_number to remove unique constraint so we can handle duplicates via import
        // The unique constraint will be re-added as a compound unique on (capex_id, capex_id_number)
        // But actually let's keep it unique globally since it's an ID
    }

    public function down(): void
    {
        Schema::table('capex_items', function (Blueprint $table) {
            $table->dropColumn(['priority_scale', 'month', 'amount_per_year', 'capex_type', 'pic']);
        });
    }
};
