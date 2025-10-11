<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('approval_request_master_items', function (Blueprint $table) {
            if (!Schema::hasColumn('approval_request_master_items', 'specification')) {
                $table->text('specification')->nullable()->after('notes');
            }
            if (!Schema::hasColumn('approval_request_master_items', 'brand')) {
                $table->string('brand')->nullable()->after('specification');
            }
            if (!Schema::hasColumn('approval_request_master_items', 'supplier_id')) {
                $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete()->after('brand');
            }
            if (!Schema::hasColumn('approval_request_master_items', 'alternative_vendor')) {
                $table->string('alternative_vendor')->nullable()->after('supplier_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('approval_request_master_items', function (Blueprint $table) {
            if (Schema::hasColumn('approval_request_master_items', 'alternative_vendor')) {
                $table->dropColumn('alternative_vendor');
            }
            if (Schema::hasColumn('approval_request_master_items', 'supplier_id')) {
                $table->dropConstrainedForeignId('supplier_id');
            }
            if (Schema::hasColumn('approval_request_master_items', 'brand')) {
                $table->dropColumn('brand');
            }
            if (Schema::hasColumn('approval_request_master_items', 'specification')) {
                $table->dropColumn('specification');
            }
        });
    }
};
