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
        Schema::table('approval_item_steps', function (Blueprint $table) {
            $table->foreignId('approval_request_item_id')
                  ->nullable() // Nullable for migration, but should be filled
                  ->after('approval_request_id')
                  ->constrained('approval_request_items')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('approval_item_steps', function (Blueprint $table) {
            $table->dropForeign(['approval_request_item_id']);
            $table->dropColumn('approval_request_item_id');
        });
    }
};
