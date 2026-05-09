<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add required_actions (JSON array) to approval_item_steps.
     * Option B: keeps existing required_action string column intact for backward compatibility.
     * New required_actions JSON can hold multiple actions per step (e.g. ['input_price', 'upload_attachment']).
     */
    public function up(): void
    {
        Schema::table('approval_item_steps', function (Blueprint $table) {
            $table->jsonb('required_actions')->nullable()->after('required_action')
                  ->comment('Array of required actions for this step (Option B, backward compatible)');
        });
    }

    public function down(): void
    {
        Schema::table('approval_item_steps', function (Blueprint $table) {
            $table->dropColumn('required_actions');
        });
    }
};
