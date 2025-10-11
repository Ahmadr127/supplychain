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
        // Drop column only if it exists
        if (Schema::hasColumn('approval_requests', 'description')) {
            Schema::table('approval_requests', function (Blueprint $table) {
                $table->dropColumn('description');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Re-create column only if it does not exist
        if (!Schema::hasColumn('approval_requests', 'description')) {
            Schema::table('approval_requests', function (Blueprint $table) {
                $table->text('description')->nullable();
            });
        }
    }
};
