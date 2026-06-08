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
        Schema::table('approval_workflow_ts_categories', function (Blueprint $table) {
            $table->dropForeign('ts_cat_item_cat_fk');
            $table->renameColumn('item_category_id', 'ts_category_id');
            $table->foreign('ts_category_id', 'fk_aw_ts_cat_ts_id')->references('id')->on('ts_categories')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('approval_workflow_ts_categories', function (Blueprint $table) {
            $table->dropForeign('fk_aw_ts_cat_ts_id');
            $table->renameColumn('ts_category_id', 'item_category_id');
            $table->foreign('item_category_id')->references('id')->on('item_categories')->onDelete('cascade');
        });
    }
};
