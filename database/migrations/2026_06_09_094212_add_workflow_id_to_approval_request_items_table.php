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
            $table->unsignedBigInteger('workflow_id')->nullable()->after('approval_request_id');
            $table->foreign('workflow_id')->references('id')->on('approval_workflows')->nullOnDelete();
        });

        // Backfill existing data
        \Illuminate\Support\Facades\DB::statement('UPDATE approval_request_items SET workflow_id = approval_requests.workflow_id FROM approval_requests WHERE approval_request_items.approval_request_id = approval_requests.id');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('approval_request_items', function (Blueprint $table) {
            $table->dropForeign(['workflow_id']);
            $table->dropColumn('workflow_id');
        });
    }
};
