<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchasing_item_vendors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchasing_item_id')->constrained('purchasing_items')->cascadeOnDelete();
            $table->foreignId('supplier_id')->constrained('suppliers');
            $table->decimal('unit_price', 18, 2);
            $table->decimal('total_price', 18, 2);
            $table->boolean('is_preferred')->default(false);
            $table->string('notes')->nullable();
            $table->timestamps();

            $table->unique(['purchasing_item_id', 'supplier_id']);
            $table->index(['purchasing_item_id']);
            $table->index(['supplier_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchasing_item_vendors');
    }
};
