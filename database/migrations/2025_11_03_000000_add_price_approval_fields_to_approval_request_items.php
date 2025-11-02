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
            // Make unit_price nullable (will be filled by manager)
            $table->bigInteger('unit_price')->nullable()->change();
            
            // Add price approval tracking fields
            $table->unsignedBigInteger('approved_price_by')->nullable()->after('unit_price');
            $table->timestamp('approved_price_at')->nullable()->after('approved_price_by');
            
            // Add foreign key
            $table->foreign('approved_price_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('approval_request_items', function (Blueprint $table) {
            $table->dropForeign(['approved_price_by']);
            $table->dropColumn(['approved_price_by', 'approved_price_at']);
            
            // Revert unit_price to not nullable (if needed)
            // Note: This might fail if there are NULL values
            // $table->bigInteger('unit_price')->nullable(false)->change();
        });
    }
};
