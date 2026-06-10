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
            $table->unsignedBigInteger('ts_category_id')->nullable()->after('needs_ts');
            $table->foreign('ts_category_id', 'fk_ari_ts_cat_id')->references('id')->on('ts_categories')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('approval_request_items', function (Blueprint $table) {
            $table->dropForeign('fk_ari_ts_cat_id');
            $table->dropColumn('ts_category_id');
        });
    }
};

