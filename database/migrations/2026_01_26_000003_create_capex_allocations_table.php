<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Tabel untuk tracking pengalokasian CapEx ID Number ke approval request.
     * Dibuat saat Manager Unit memilih CapEx ID di step Approver 1.
     */
    public function up(): void
    {
        Schema::create('capex_allocations', function (Blueprint $table) {
            $table->id();
            
            // References
            $table->foreignId('capex_id_number_id')->constrained('capex_id_numbers')->cascadeOnDelete();
            $table->foreignId('approval_request_id')->constrained('approval_requests')->cascadeOnDelete();
            $table->foreignId('approval_request_item_id')->nullable()->constrained('approval_request_items')->nullOnDelete();
            
            // Allocation details
            $table->decimal('allocated_amount', 15, 2);      // Jumlah yang dialokasikan
            $table->date('allocation_date');
            
            // Status tracking
            $table->enum('status', ['pending', 'confirmed', 'cancelled', 'released'])->default('pending');
            
            // Approval tracking
            $table->foreignId('allocated_by')->nullable()->constrained('users')->nullOnDelete();  // Manager Unit yang memilih
            $table->foreignId('confirmed_by')->nullable()->constrained('users')->nullOnDelete(); // Yang mengkonfirmasi
            $table->timestamp('confirmed_at')->nullable();
            
            // Cancellation tracking
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();
            
            // Notes
            $table->text('notes')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index('capex_id_number_id');
            $table->index('approval_request_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('capex_allocations');
    }
};
