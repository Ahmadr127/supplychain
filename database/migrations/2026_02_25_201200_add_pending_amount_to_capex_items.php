<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add pending_amount to capex_items.
     * pending_amount = total budget yang sedang dalam proses pengajuan (belum final).
     * available_amount = budget_amount - used_amount - pending_amount
     */
    public function up(): void
    {
        Schema::table('capex_items', function (Blueprint $table) {
            $table->decimal('pending_amount', 15, 2)->default(0)->after('used_amount')
                  ->comment('Total nominal yang sedang dalam proses pengajuan (belum approved/rejected)');
        });
    }

    public function down(): void
    {
        Schema::table('capex_items', function (Blueprint $table) {
            $table->dropColumn('pending_amount');
        });
    }
};
