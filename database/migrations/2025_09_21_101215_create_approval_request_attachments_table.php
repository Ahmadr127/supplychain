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
        Schema::create('approval_request_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('approval_request_id')->constrained('approval_requests')->onDelete('cascade');
            $table->string('original_name'); // Nama file asli
            $table->string('file_name'); // Nama file yang disimpan
            $table->string('file_path'); // Path file di storage
            $table->string('mime_type'); // MIME type file
            $table->bigInteger('file_size'); // Ukuran file dalam bytes
            $table->text('description')->nullable(); // Deskripsi file
            $table->timestamps();
            
            // Indexes
            $table->index(['approval_request_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('approval_request_attachments');
    }
};