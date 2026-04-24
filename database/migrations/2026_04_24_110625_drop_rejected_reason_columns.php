<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Drop from approval_item_steps
        if (Schema::hasColumn('approval_item_steps', 'rejected_reason')) {
            Schema::table('approval_item_steps', function (Blueprint $table) {
                $table->dropColumn('rejected_reason');
            });
        }

        // Drop from approval_request_items
        if (Schema::hasColumn('approval_request_items', 'rejected_reason')) {
            Schema::table('approval_request_items', function (Blueprint $table) {
                $table->dropColumn('rejected_reason');
            });
        }
    }

    public function down(): void
    {
        Schema::table('approval_item_steps', function (Blueprint $table) {
            $table->text('rejected_reason')->nullable()->after('comments');
        });

        Schema::table('approval_request_items', function (Blueprint $table) {
            $table->text('rejected_reason')->nullable();
        });
    }
};
