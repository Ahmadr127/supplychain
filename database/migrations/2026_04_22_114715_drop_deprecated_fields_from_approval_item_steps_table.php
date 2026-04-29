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
        Schema::table('approval_item_steps', function (Blueprint $table) {
            $table->dropColumn([
                'is_conditional',
                'condition_type',
                'condition_value',
                'skip_reason',
                'skipped_at',
                'skipped_by'
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('approval_item_steps', function (Blueprint $table) {
            $table->boolean('is_conditional')->default(false)->after('required_action');
            $table->string('condition_type')->nullable()->after('is_conditional');
            $table->decimal('condition_value', 15, 2)->nullable()->after('condition_type');
            $table->text('skip_reason')->nullable()->after('condition_value');
            $table->timestamp('skipped_at')->nullable()->after('skip_reason');
            $table->unsignedBigInteger('skipped_by')->nullable()->after('skipped_at');
        });
    }
};
