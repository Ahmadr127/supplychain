<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('approval_request_line_attachments', function (Blueprint $table) {
            $table->id();
            // Define column then explicit FK with short name to avoid identifier too long
            $table->unsignedBigInteger('approval_request_line_id');
            $table->string('original_name');
            $table->string('file_name');
            $table->string('file_path');
            $table->string('mime_type');
            $table->unsignedBigInteger('file_size');
            $table->timestamps();

            // Short foreign key name: fk_arla_line
            $table->foreign('approval_request_line_id', 'fk_arla_line')
                  ->references('id')->on('approval_request_lines')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('approval_request_line_attachments');
    }
};
