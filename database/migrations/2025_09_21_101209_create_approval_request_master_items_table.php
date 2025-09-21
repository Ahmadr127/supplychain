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
        Schema::create('approval_request_master_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('approval_request_id')->constrained('approval_requests')->onDelete('cascade');
            $table->foreignId('master_item_id')->constrained('master_items')->onDelete('cascade');
            $table->integer('quantity')->default(1);
            $table->decimal('unit_price', 15, 2)->nullable(); // Harga per unit saat request dibuat
            $table->decimal('total_price', 15, 2)->nullable(); // Total harga (quantity * unit_price)
            $table->text('notes')->nullable(); // Catatan khusus untuk item ini
            $table->timestamps();
            
            // Indexes
            $table->index(['approval_request_id', 'master_item_id'], 'ar_mi_index');
            $table->unique(['approval_request_id', 'master_item_id'], 'ar_mi_unique'); // Prevent duplicate items in same request
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('approval_request_master_items');
    }
};