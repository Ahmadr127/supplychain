<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('import_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('history_id')->constrained('import_histories')->onDelete('cascade');
            $table->integer('row_number');
            $table->json('row_data');    // raw row values before mapping
            $table->json('errors');      // validation/insert error messages
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('import_logs');
    }
};
