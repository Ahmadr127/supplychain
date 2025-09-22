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
        Schema::table('approval_requests', function (Blueprint $table) {
            $table->enum('priority', ['low', 'normal', 'high', 'urgent'])->default('normal')->after('description');
            $table->boolean('is_cto_request')->default(false)->after('priority');
            $table->index(['priority', 'is_cto_request']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('approval_requests', function (Blueprint $table) {
            $table->dropIndex(['priority', 'is_cto_request']);
            $table->dropColumn(['priority', 'is_cto_request']);
        });
    }
};
