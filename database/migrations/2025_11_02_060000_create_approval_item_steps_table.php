<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('approval_item_steps', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('approval_request_id');
            $table->unsignedBigInteger('master_item_id');
            $table->unsignedInteger('step_number');
            $table->string('step_name');
            $table->enum('approver_type', ['user','role','department_manager','requester_department_manager','any_department_manager']);
            $table->unsignedBigInteger('approver_id')->nullable();
            $table->unsignedBigInteger('approver_role_id')->nullable();
            $table->unsignedBigInteger('approver_department_id')->nullable();
            $table->enum('status', ['pending','approved','rejected'])->default('pending');
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->text('comments')->nullable();
            $table->timestamps();

            $table->foreign('approval_request_id')->references('id')->on('approval_requests')->onDelete('cascade');
            $table->foreign('master_item_id')->references('id')->on('master_items');
            $table->index(['approval_request_id','master_item_id']);
            $table->index(['approval_request_id','step_number']);
            $table->index(['master_item_id']);
            $table->index(['status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('approval_item_steps');
    }
};
