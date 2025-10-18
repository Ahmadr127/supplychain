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
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('type')->default('string'); // string, integer, boolean, json
            $table->string('group')->default('general');
            $table->string('description')->nullable();
            $table->timestamps();
        });
        
        // Insert default settings for FS document thresholds
        DB::table('settings')->insert([
            [
                'key' => 'fs_threshold_per_item',
                'value' => '100000000', // 100 juta per item subtotal
                'type' => 'integer',
                'group' => 'approval_request',
                'description' => 'Threshold harga subtotal per item untuk menampilkan dokumen FS (dalam Rupiah)',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'fs_threshold_total',
                'value' => '500000000', // 500 juta total semua item
                'type' => 'integer', 
                'group' => 'approval_request',
                'description' => 'Threshold total harga semua item untuk menampilkan dokumen FS (dalam Rupiah)',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'fs_document_enabled',
                'value' => 'true',
                'type' => 'boolean',
                'group' => 'approval_request',
                'description' => 'Aktifkan/nonaktifkan fitur dokumen FS',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
