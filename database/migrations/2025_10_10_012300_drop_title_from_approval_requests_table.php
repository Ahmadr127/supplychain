<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('approval_requests', function (Blueprint $table) {
            if (Schema::hasColumn('approval_requests', 'title')) {
                $table->dropColumn('title');
            }
        });
    }

    public function down(): void
    {
        Schema::table('approval_requests', function (Blueprint $table) {
            if (!Schema::hasColumn('approval_requests', 'title')) {
                $table->string('title')->nullable()->after('requester_id');
            }
        });
    }
};
