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
        Schema::create('approval_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('request_id')->constrained('approval_requests')->onDelete('cascade');
            $table->integer('step_number');
            $table->string('step_name');
            $table->enum('approver_type', ['user', 'role', 'department_manager', 'department_level']); // Tipe approver
            $table->foreignId('approver_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->foreignId('approver_role_id')->nullable()->constrained('roles')->onDelete('cascade');
            $table->foreignId('approver_department_id')->nullable()->constrained('departments')->onDelete('cascade');
            $table->integer('approver_level')->nullable(); // Level departemen yang bisa approve
            $table->enum('status', ['pending', 'approved', 'rejected', 'skipped'])->default('pending');
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();
            $table->text('comments')->nullable();
            $table->timestamps();
            
            $table->index(['request_id', 'step_number']);
            $table->index(['approver_id', 'status']);
            $table->index(['status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('approval_steps');
    }
};
