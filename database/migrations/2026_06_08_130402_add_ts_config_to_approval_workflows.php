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
        Schema::table('approval_workflows', function (Blueprint $table) {
            $table->string('ts_approver_type')->nullable()->after('purchasing_step_config')->comment('Tipe approver untuk TS (user/role/department)');
            $table->unsignedBigInteger('ts_approver_id')->nullable()->after('ts_approver_type')->comment('ID spesifik untuk TS jika tipe = user');
            $table->unsignedBigInteger('ts_approver_role_id')->nullable()->after('ts_approver_id')->comment('Role ID spesifik untuk TS jika tipe = role');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('approval_workflows', function (Blueprint $table) {
            $table->dropColumn(['ts_approver_type', 'ts_approver_id', 'ts_approver_role_id']);
        });
    }
};
