<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add purchasing_step_config JSON column to approval_workflows.
     *
     * This column stores which purchasing steps are enabled for this workflow
     * and their configuration (order, allow_skip).
     *
     * Example value:
     * [
     *   {"step_key":"benchmarking",     "label":"Benchmarking Vendor",   "enabled":true,  "order":1, "allow_skip":false},
     *   {"step_key":"trial",            "label":"Trial Vendor",           "enabled":false, "order":2, "allow_skip":true},
     *   {"step_key":"preferred_vendor", "label":"Pilih Preferred Vendor", "enabled":true,  "order":3, "allow_skip":false},
     *   {"step_key":"po",               "label":"Input PO",               "enabled":true,  "order":4, "allow_skip":false},
     *   {"step_key":"invoice_grn_done", "label":"Invoice & GRN (Done)",   "enabled":true,  "order":5, "allow_skip":false}
     * ]
     *
     * NULL means "use default" (all steps enabled, standard order).
     */
    public function up(): void
    {
        Schema::table('approval_workflows', function (Blueprint $table) {
            $table->json('purchasing_step_config')
                ->nullable()
                ->after('priority')
                ->comment('JSON config of purchasing steps enabled for this workflow. NULL = all steps enabled.');
        });
    }

    public function down(): void
    {
        Schema::table('approval_workflows', function (Blueprint $table) {
            $table->dropColumn('purchasing_step_config');
        });
    }
};
