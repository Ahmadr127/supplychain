<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Buat tabel capex_allocations baru dengan relasi ke capex_items.
     * Menggantikan tabel lama yang sudah di-drop oleh recreate_capex_tables.
     */
    public function up(): void
    {
        // Jika tabel sudah ada (misal dari migrasi lain), skip
        if (Schema::hasTable('capex_allocations')) {
            // Cek apakah kolom capex_item_id sudah ada
            if (!Schema::hasColumn('capex_allocations', 'capex_item_id')) {
                Schema::table('capex_allocations', function (Blueprint $table) {
                    $table->unsignedBigInteger('capex_item_id')->nullable()->after('id');
                    $table->foreign('capex_item_id')
                          ->references('id')
                          ->on('capex_items')
                          ->nullOnDelete();
                });
            }
            return;
        }

        // Buat tabel baru
        Schema::create('capex_allocations', function (Blueprint $table) {
            $table->id();

            // Referensi ke capex item yang dialokasikan
            $table->unsignedBigInteger('capex_item_id')->nullable();
            $table->foreign('capex_item_id')
                  ->references('id')
                  ->on('capex_items')
                  ->nullOnDelete();

            // Referensi ke approval request
            $table->foreignId('approval_request_id')
                  ->constrained('approval_requests')
                  ->cascadeOnDelete();

            $table->foreignId('approval_request_item_id')
                  ->nullable()
                  ->constrained('approval_request_items')
                  ->nullOnDelete();

            // Detail alokasi
            $table->decimal('allocated_amount', 15, 2);
            $table->date('allocation_date');

            // Status: pending → confirmed atau cancelled
            $table->enum('status', ['pending', 'confirmed', 'cancelled'])
                  ->default('pending');

            // Tracking
            $table->foreignId('allocated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('confirmed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('confirmed_at')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();

            $table->text('notes')->nullable();
            $table->timestamps();

            // Indexes
            $table->index('capex_item_id');
            $table->index('approval_request_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('capex_allocations');
    }
};
