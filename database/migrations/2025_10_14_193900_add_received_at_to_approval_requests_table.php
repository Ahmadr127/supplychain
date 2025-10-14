<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('approval_requests', function (Blueprint $table) {
            if (!Schema::hasColumn('approval_requests', 'received_at')) {
                $table->dateTime('received_at')->nullable()->after('is_specific_type');
            }
        });
    }

    public function down(): void
    {
        Schema::table('approval_requests', function (Blueprint $table) {
            if (Schema::hasColumn('approval_requests', 'received_at')) {
                $table->dropColumn('received_at');
            }
        });
    }
};
