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
        Schema::table('master_items', function (Blueprint $table) {
            // Remove unnecessary columns
            $table->dropColumn([
                'barcode',
                'brand',
                'manufacturer',
                'specification',
                'minimum_stock',
                'maximum_stock'
            ]);
            
            // Add new stock column
            $table->integer('stock')->default(0)->after('unit_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('master_items', function (Blueprint $table) {
            // Remove the new stock column
            $table->dropColumn('stock');
            
            // Add back the removed columns
            $table->string('barcode')->nullable()->unique()->after('description');
            $table->string('brand')->nullable()->after('unit_id');
            $table->string('manufacturer')->nullable()->after('brand');
            $table->string('specification')->nullable()->after('manufacturer');
            $table->integer('minimum_stock')->default(0)->after('specification');
            $table->integer('maximum_stock')->default(0)->after('minimum_stock');
        });
    }
};