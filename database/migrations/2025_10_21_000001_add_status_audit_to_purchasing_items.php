<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('purchasing_items', function (Blueprint $table) {
            $table->timestamp('status_changed_at')->nullable()->after('status');
            $table->unsignedBigInteger('status_changed_by')->nullable()->after('status_changed_at');
            $table->text('done_notes')->nullable()->after('proc_cycle_days');

            $table->foreign('status_changed_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('purchasing_items', function (Blueprint $table) {
            $table->dropForeign(['status_changed_by']);
            $table->dropColumn(['status_changed_at', 'status_changed_by', 'done_notes']);
        });
    }
};
