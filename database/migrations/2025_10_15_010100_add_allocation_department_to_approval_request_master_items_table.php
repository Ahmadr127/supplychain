<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('approval_request_master_items', function (Blueprint $table) {
            $table->foreignId('allocation_department_id')
                ->nullable()
                ->after('alternative_vendor')
                ->constrained('departments')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('approval_request_master_items', function (Blueprint $table) {
            if (Schema::hasColumn('approval_request_master_items', 'allocation_department_id')) {
                $table->dropConstrainedForeignId('allocation_department_id');
            }
        });
    }
};
