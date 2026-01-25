<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Tabel untuk menyimpan jenis pengadaan:
     * - BARANG_BARU (Pengadaan Barang Baru)
     * - PEREMAJAAN (Peremajaan/Renewal)
     */
    public function up(): void
    {
        Schema::create('procurement_types', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);                    // "Pengadaan Barang Baru", "Peremajaan"
            $table->string('code', 20)->unique();           // "BARANG_BARU", "PEREMAJAAN"
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('procurement_types');
    }
};
