<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('approval_request_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('approval_request_id')->constrained('approval_requests')->onDelete('cascade');
            $table->foreignId('master_item_id')->constrained('master_items')->onDelete('cascade');
            $table->integer('quantity')->default(1);
            $table->decimal('unit_price', 15, 2)->nullable();
            $table->decimal('total_price', 15, 2)->nullable();
            $table->text('notes')->nullable();
            $table->text('specification')->nullable();
            $table->string('brand')->nullable();
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
            $table->string('alternative_vendor')->nullable();
            $table->foreignId('allocation_department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->string('letter_number')->nullable();
            $table->string('fs_document')->nullable();
            // Per-item approval columns (align with request-level statuses)
            $table->string('status')->default('pending'); // pending|on progress|approved|rejected|cancelled
            $table->foreignId('assignee_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('rejected_reason')->nullable();
            $table->timestamps();

            $table->index(['approval_request_id']);
            $table->index(['status']);
            $table->index(['allocation_department_id']);
            $table->unique(['approval_request_id','master_item_id'], 'uniq_req_item');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('approval_request_items');
    }
};
