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
        Schema::table('approval_request_items', function (Blueprint $table) {
            $table->foreignId('capex_item_id')->nullable()->constrained('capex_items')->nullOnDelete()->after('master_item_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('approval_request_items', function (Blueprint $table) {
            $table->dropForeign(['capex_item_id']);
            $table->dropColumn('capex_item_id');
        });
    }
};
