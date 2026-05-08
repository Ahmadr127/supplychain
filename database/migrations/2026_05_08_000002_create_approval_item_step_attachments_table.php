<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabel untuk menyimpan lampiran yang diunggah saat proses approval step.
     * Dibuat terpisah dari approval_request_item_files (lampiran awal requester)
     * dan fs_documents (FS dari keuangan) untuk kejelasan audit trail.
     */
    public function up(): void
    {
        Schema::create('approval_item_step_attachments', function (Blueprint $table) {
            $table->id();

            // Reference ke step yang memiliki lampiran ini
            $table->foreignId('approval_item_step_id')
                  ->constrained('approval_item_steps')
                  ->cascadeOnDelete();

            // Denormalized references for easier querying
            $table->unsignedBigInteger('approval_request_id');
            $table->unsignedBigInteger('approval_request_item_id');

            // File info
            $table->string('original_name');
            $table->string('path');                  // Storage path (public disk)
            $table->string('mime')->nullable();
            $table->unsignedBigInteger('size')->nullable(); // bytes

            // Uploader
            $table->foreignId('uploaded_by')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();

            $table->timestamps();

            $table->index('approval_item_step_id');
            $table->index('approval_request_id');
            $table->index('approval_request_item_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('approval_item_step_attachments');
    }
};
