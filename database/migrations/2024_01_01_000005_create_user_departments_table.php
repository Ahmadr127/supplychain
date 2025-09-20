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
        Schema::create('user_departments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('department_id')->constrained()->onDelete('cascade');
            $table->string('position')->nullable(); // Jabatan dalam departemen
            $table->boolean('is_primary')->default(false); // Departemen utama user
            $table->boolean('is_manager')->default(false); // Apakah user adalah manager departemen
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->timestamps();
            
            $table->unique(['user_id', 'department_id']);
            $table->index(['user_id', 'is_primary']);
            $table->index(['department_id', 'is_manager']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_departments');
    }
};
