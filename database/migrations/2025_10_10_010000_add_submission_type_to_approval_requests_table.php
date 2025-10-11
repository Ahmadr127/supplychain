<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('approval_requests', function (Blueprint $table) {
            if (!Schema::hasColumn('approval_requests', 'submission_type')) {
                $table->string('submission_type')->default('barang')->after('is_specific_type');
            }
        });
    }

    public function down(): void
    {
        Schema::table('approval_requests', function (Blueprint $table) {
            if (Schema::hasColumn('approval_requests', 'submission_type')) {
                $table->dropColumn('submission_type');
            }
        });
    }
};
