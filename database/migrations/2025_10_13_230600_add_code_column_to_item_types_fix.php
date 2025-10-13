<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('item_types') && !Schema::hasColumn('item_types', 'code')) {
            Schema::table('item_types', function (Blueprint $table) {
                $table->string('code', 10)->nullable()->after('name');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('item_types') && Schema::hasColumn('item_types', 'code')) {
            Schema::table('item_types', function (Blueprint $table) {
                $table->dropColumn('code');
            });
        }
    }
};
