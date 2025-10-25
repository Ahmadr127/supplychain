<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchasing_items', function (Blueprint $table) {
            if (!Schema::hasColumn('purchasing_items', 'benchmark_notes')) {
                $table->text('benchmark_notes')->nullable()->after('status_changed_by');
            }
        });
    }

    public function down(): void
    {
        Schema::table('purchasing_items', function (Blueprint $table) {
            if (Schema::hasColumn('purchasing_items', 'benchmark_notes')) {
                $table->dropColumn('benchmark_notes');
            }
        });
    }
};
