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
        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique(); // Kode departemen (IT, HR, FIN, etc)
            $table->text('description')->nullable();
            $table->foreignId('parent_id')->nullable()->constrained('departments')->onDelete('cascade');
            $table->foreignId('manager_id')->nullable()->constrained('users')->onDelete('set null');
            $table->integer('level')->default(1); // Level hierarki (1=unit, 2=management, 3=direktur)
            $table->integer('approval_level')->default(1); // Level approval yang diperlukan
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index(['parent_id', 'level']);
            $table->index(['manager_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('departments');
    }
};
