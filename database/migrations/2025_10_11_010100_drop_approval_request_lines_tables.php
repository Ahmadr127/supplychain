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
        // Drop child table first due to FK
        if (Schema::hasTable('approval_request_line_attachments')) {
            Schema::drop('approval_request_line_attachments');
        }
        if (Schema::hasTable('approval_request_lines')) {
            Schema::drop('approval_request_lines');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Recreate tables with minimal structure from original migrations
        if (!Schema::hasTable('approval_request_lines')) {
            Schema::create('approval_request_lines', function (Blueprint $table) {
                $table->id();
                $table->foreignId('approval_request_id')->constrained('approval_requests')->onDelete('cascade');
                $table->string('item_name');
                $table->integer('quantity');
                $table->string('unit_name')->nullable();
                $table->text('specification')->nullable();
                $table->string('brand')->nullable();
                $table->text('notes')->nullable();
                $table->string('alternative_vendor')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('approval_request_line_attachments')) {
            Schema::create('approval_request_line_attachments', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('approval_request_line_id');
                $table->string('original_name');
                $table->string('file_name');
                $table->string('file_path');
                $table->string('mime_type');
                $table->unsignedBigInteger('file_size');
                $table->timestamps();

                $table->foreign('approval_request_line_id', 'fk_arla_line')
                      ->references('id')->on('approval_request_lines')
                      ->onDelete('cascade');
            });
        }
    }
};
