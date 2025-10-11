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
        // Drop item-level attachments table if exists
        if (Schema::hasTable('approval_request_item_attachments')) {
            Schema::drop('approval_request_item_attachments');
        }

        // Drop request-level attachments table if exists
        if (Schema::hasTable('approval_request_attachments')) {
            Schema::drop('approval_request_attachments');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Recreate approval_request_attachments table as originally defined
        if (!Schema::hasTable('approval_request_attachments')) {
            Schema::create('approval_request_attachments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('approval_request_id')->constrained('approval_requests')->onDelete('cascade');
                $table->string('original_name');
                $table->string('file_name');
                $table->string('file_path');
                $table->string('mime_type');
                $table->bigInteger('file_size');
                $table->text('description')->nullable();
                $table->timestamps();
                $table->index(['approval_request_id']);
            });
        }

        // Recreate approval_request_item_attachments table (empty skeleton as originally defined)
        if (!Schema::hasTable('approval_request_item_attachments')) {
            Schema::create('approval_request_item_attachments', function (Blueprint $table) {
                $table->id();
                $table->timestamps();
            });
        }
    }
};
