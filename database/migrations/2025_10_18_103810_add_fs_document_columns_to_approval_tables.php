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
        // Add fs_document column to approval_requests table (global FS document)
        if (!Schema::hasColumn('approval_requests', 'fs_document')) {
            Schema::table('approval_requests', function (Blueprint $table) {
                $table->string('fs_document')->nullable()->after('status');
            });
        }
        
        // Add fs_document column to approval_request_master_items pivot table (per-item FS document)
        if (!Schema::hasColumn('approval_request_master_items', 'fs_document')) {
            Schema::table('approval_request_master_items', function (Blueprint $table) {
                $table->string('fs_document')->nullable()->after('letter_number');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('approval_requests', function (Blueprint $table) {
            $table->dropColumn('fs_document');
        });
        
        Schema::table('approval_request_master_items', function (Blueprint $table) {
            $table->dropColumn('fs_document');
        });
    }
};
