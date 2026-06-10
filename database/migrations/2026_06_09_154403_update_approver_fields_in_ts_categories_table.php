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
        Schema::table('ts_categories', function (Blueprint $table) {
            // Drop old user_id column
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');

            // Add new approver columns
            $table->string('ts_approver_type', 50)->nullable(); // 'user', 'role', 'department_manager'
            $table->unsignedBigInteger('ts_approver_id')->nullable();
            $table->unsignedBigInteger('ts_approver_role_id')->nullable();

            $table->foreign('ts_approver_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('ts_approver_role_id')->references('id')->on('roles')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ts_categories', function (Blueprint $table) {
            $table->dropForeign(['ts_approver_id']);
            $table->dropForeign(['ts_approver_role_id']);
            $table->dropColumn(['ts_approver_type', 'ts_approver_id', 'ts_approver_role_id']);

            $table->unsignedBigInteger('user_id')->nullable();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });
    }
};
