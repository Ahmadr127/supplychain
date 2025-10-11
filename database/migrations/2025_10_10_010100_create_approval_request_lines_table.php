<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
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

    public function down(): void
    {
        Schema::dropIfExists('approval_request_lines');
    }
};
