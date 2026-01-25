<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Tabel terpisah untuk mengelola CapEx ID Number.
     * Manager Unit akan memilih CapEx ID saat approval step pertama.
     */
    public function up(): void
    {
        Schema::create('capex_id_numbers', function (Blueprint $table) {
            $table->id();
            
            // Capex identification
            $table->string('capex_number', 50)->unique();   // e.g., "CAPEX-2026-001"
            $table->integer('capex_year');                   // Tahun anggaran
            $table->string('capex_category', 100)->nullable(); // Kategori capex (IT, Operasional, dll)
            
            // Budget information
            $table->decimal('budget_amount', 15, 2);         // Total anggaran capex
            $table->decimal('used_amount', 15, 2)->default(0); // Jumlah yang sudah terpakai
            
            // Status
            $table->enum('status', ['active', 'exhausted', 'closed'])->default('active');
            
            // Metadata
            $table->text('description')->nullable();
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            
            // Period validity
            $table->date('valid_from')->nullable();
            $table->date('valid_until')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index('capex_year');
            $table->index('status');
            $table->index('department_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('capex_id_numbers');
    }
};
