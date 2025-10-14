<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchasing_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('approval_request_id')->constrained('approval_requests')->cascadeOnDelete();
            $table->foreignId('master_item_id')->constrained('master_items');
            $table->unsignedBigInteger('pivot_ref_id')->nullable(); // reference to approval_request_master_items id if needed
            $table->integer('quantity')->default(1);
            $table->enum('status', ['unprocessed','benchmarking','comparing','selected','po_issued','grn_received','done'])->default('unprocessed');
            $table->foreignId('preferred_vendor_id')->nullable()->constrained('suppliers');
            $table->decimal('preferred_unit_price', 18, 2)->nullable();
            $table->decimal('preferred_total_price', 18, 2)->nullable();
            $table->string('invoice_number')->nullable();
            $table->string('po_number')->nullable();
            $table->date('grn_date')->nullable();
            $table->integer('proc_cycle_days')->nullable();
            $table->timestamps();

            $table->index(['approval_request_id']);
            $table->index(['status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchasing_items');
    }
};
