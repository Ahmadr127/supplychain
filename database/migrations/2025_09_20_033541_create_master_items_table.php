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
        Schema::create('master_items', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->text('description')->nullable();
            $table->string('barcode')->nullable()->unique();
            $table->decimal('hna', 15, 2); // Harga Netto Apotek
            $table->decimal('ppn_percentage', 5, 2)->default(11.00); // PPN percentage
            $table->decimal('ppn_amount', 15, 2)->default(0); // PPN amount calculated
            $table->decimal('total_price', 15, 2); // HNA + PPN
            
            // Foreign keys
            $table->foreignId('item_type_id')->constrained('item_types');
            $table->foreignId('item_category_id')->constrained('item_categories');
            $table->foreignId('commodity_id')->constrained('commodities');
            $table->foreignId('unit_id')->constrained('units');
            
            // Additional fields
            $table->string('brand')->nullable();
            $table->string('manufacturer')->nullable();
            $table->string('specification')->nullable();
            $table->integer('minimum_stock')->default(0);
            $table->integer('maximum_stock')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            // Indexes
            $table->index(['item_type_id', 'is_active']);
            $table->index(['item_category_id', 'is_active']);
            $table->index(['commodity_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('master_items');
    }
};