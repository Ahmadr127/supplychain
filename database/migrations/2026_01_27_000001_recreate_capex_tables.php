<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Redesign CapEx tables:
     * - capexes: Header per Department per Year
     * - capex_items: Individual items with unique CapEx ID Number
     */
    public function up(): void
    {
        // Drop old tables if exist
        Schema::dropIfExists('capex_allocations');
        
        // Drop foreign key in approval_requests if exists
        if (Schema::hasColumn('approval_requests', 'capex_id_number_id')) {
            Schema::table('approval_requests', function (Blueprint $table) {
                $table->dropForeign(['capex_id_number_id']);
                $table->dropColumn('capex_id_number_id');
            });
        }

        // Drop foreign key in approval_item_steps if exists
        if (Schema::hasColumn('approval_item_steps', 'selected_capex_id')) {
            Schema::table('approval_item_steps', function (Blueprint $table) {
                $table->dropForeign(['selected_capex_id']);
                $table->dropColumn('selected_capex_id');
            });
        }
        
        Schema::dropIfExists('capex_id_numbers');

        // Create capexes table (header per department per year)
        Schema::create('capexes', function (Blueprint $table) {
            $table->id();
            
            $table->foreignId('department_id')->constrained('departments')->onDelete('cascade');
            $table->integer('fiscal_year');
            
            // Status
            $table->enum('status', ['draft', 'active', 'closed'])->default('active');
            
            // Metadata
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            
            $table->timestamps();
            
            // Unique constraint: one capex per department per year
            $table->unique(['department_id', 'fiscal_year']);
        });

        // Create capex_items table (individual items with CapEx ID)
        Schema::create('capex_items', function (Blueprint $table) {
            $table->id();
            
            $table->foreignId('capex_id')->constrained('capexes')->onDelete('cascade');
            
            // Unique CapEx ID Number (e.g., CAPEX-IT-2026-001)
            $table->string('capex_id_number', 50)->unique();
            
            // Item details
            $table->string('item_name', 255);
            $table->text('description')->nullable();
            $table->string('category', 100)->nullable(); // IT, Operasional, Medis, dll
            
            // Budget
            $table->decimal('budget_amount', 15, 2); // Anggaran untuk item ini
            $table->decimal('used_amount', 15, 2)->default(0); // Yang sudah terpakai
            
            // Status
            $table->enum('status', ['available', 'partially_used', 'exhausted', 'cancelled'])->default('available');
            
            // Link to approval request item (when used)
            $table->foreignId('approval_request_id')->nullable()->constrained('approval_requests')->nullOnDelete();
            $table->foreignId('approval_request_item_id')->nullable()->constrained('approval_request_items')->nullOnDelete();
            
            $table->timestamps();
            
            // Indexes
            $table->index('capex_id');
            $table->index('status');
            $table->index('category');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('capex_items');
        Schema::dropIfExists('capexes');
    }
};
