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
            if (Schema::hasColumn('approval_requests', 'title')) {
                $table->dropColumn('title');
            }
            if (!Schema::hasColumn('approval_requests', 'submission_type_id')) {
                $table->foreignId('submission_type_id')
                    ->after('requester_id')
                    ->constrained('submission_types')
                    ->onDelete('restrict');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('approval_requests', function (Blueprint $table) {
            if (Schema::hasColumn('approval_requests', 'submission_type_id')) {
                $table->dropConstrainedForeignId('submission_type_id');
            }
            if (!Schema::hasColumn('approval_requests', 'title')) {
                $table->string('title')->nullable();
            }
        });
    }
};
