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
            // Add conditional step fields
            $table->boolean('is_conditional')->default(false)->after('required_action')
                ->comment('Whether this step is conditional (can be skipped)');
            $table->string('condition_type', 50)->nullable()->after('is_conditional')
                ->comment('Type of condition: total_price, item_count, etc.');
            $table->decimal('condition_value', 15, 2)->nullable()->after('condition_type')
                ->comment('Threshold value for the condition');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('approval_item_steps', function (Blueprint $table) {
            $table->dropColumn(['is_conditional', 'condition_type', 'condition_value']);
        });
    }
};
