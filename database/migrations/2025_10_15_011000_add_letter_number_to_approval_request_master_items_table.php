<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('approval_request_master_items', function (Blueprint $table) {
            $table->string('letter_number')->nullable()->after('alternative_vendor');
        });
    }

    public function down(): void
    {
        Schema::table('approval_request_master_items', function (Blueprint $table) {
            if (Schema::hasColumn('approval_request_master_items', 'letter_number')) {
                $table->dropColumn('letter_number');
            }
        });
    }
};
