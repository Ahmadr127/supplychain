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
        if (!Schema::hasColumn('master_items', 'stock')) {
            Schema::table('master_items', function (Blueprint $table) {
                $table->integer('stock')->default(0)->after('unit_id');
                $table->index('stock');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('master_items', 'stock')) {
            Schema::table('master_items', function (Blueprint $table) {
                $table->dropIndex(['stock']);
                $table->dropColumn('stock');
            });
        }
    }
};
