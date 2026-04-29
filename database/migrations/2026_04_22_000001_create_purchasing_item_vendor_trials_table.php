<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchasing_item_vendor_trials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchasing_item_vendor_id')
                ->constrained('purchasing_item_vendors')
                ->cascadeOnDelete();

            $table->text('trial_notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['purchasing_item_vendor_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchasing_item_vendor_trials');
    }
};

