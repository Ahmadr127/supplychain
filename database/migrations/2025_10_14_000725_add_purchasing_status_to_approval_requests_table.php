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
        Schema::table('approval_requests', function (Blueprint $table) {
            if (!Schema::hasColumn('approval_requests', 'purchasing_status')) {
                $table->string('purchasing_status')->default('unprocessed')->after('status');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('approval_requests', function (Blueprint $table) {
            if (Schema::hasColumn('approval_requests', 'purchasing_status')) {
                $table->dropColumn('purchasing_status');
            }
        });
    }
};
