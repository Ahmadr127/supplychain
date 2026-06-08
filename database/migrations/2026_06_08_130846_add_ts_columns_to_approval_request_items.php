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
            $table->boolean('needs_ts')->default(false)->after('status')->comment('Apakah item ini butuh Technical Support');
            $table->string('ts_status')->default('pending')->after('needs_ts')->comment('pending, done');
            $table->text('ts_specification')->nullable()->after('ts_status')->comment('Spesifikasi lengkap dari TS');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('approval_request_items', function (Blueprint $table) {
            $table->dropColumn(['needs_ts', 'ts_status', 'ts_specification']);
        });
    }
};
