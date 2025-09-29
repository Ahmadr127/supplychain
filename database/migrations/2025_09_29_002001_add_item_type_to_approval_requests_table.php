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
        Schema::table('approval_requests', function (Blueprint $table) {
            $table->foreignId('item_type_id')->nullable()->constrained('item_types')->onDelete('set null');
            $table->boolean('is_specific_type')->default(false)->comment('True if request is for specific item type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('approval_requests', function (Blueprint $table) {
            $table->dropForeign(['item_type_id']);
            $table->dropColumn(['item_type_id', 'is_specific_type']);
        });
    }
};