<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            $table->integer('level')->default(1)->after('manager_id');
            // 1 = Unit/Departemen Bawahan
            // 2 = Level Direktur/Management Tinggi
        });
        
        // Update existing data
        // Set level 2 for departments with parent_id = null (top level)
        DB::table('departments')
            ->whereNull('parent_id')
            ->update(['level' => 2]);
            
        // Set level 1 for departments with parent_id != null (sub departments)
        DB::table('departments')
            ->whereNotNull('parent_id')
            ->update(['level' => 1]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            $table->dropColumn('level');
        });
    }
};
