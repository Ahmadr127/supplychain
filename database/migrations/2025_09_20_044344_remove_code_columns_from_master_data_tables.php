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
        // Remove code column from item_types table
        Schema::table('item_types', function (Blueprint $table) {
            $table->dropColumn('code');
        });

        // Remove code column from item_categories table
        Schema::table('item_categories', function (Blueprint $table) {
            $table->dropColumn('code');
        });

        // Remove code column from commodities table
        Schema::table('commodities', function (Blueprint $table) {
            $table->dropColumn('code');
        });

        // Remove code column from units table
        Schema::table('units', function (Blueprint $table) {
            $table->dropColumn('code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Add back code column to item_types table
        Schema::table('item_types', function (Blueprint $table) {
            $table->string('code')->unique()->nullable()->after('name');
        });

        // Add back code column to item_categories table
        Schema::table('item_categories', function (Blueprint $table) {
            $table->string('code')->unique()->nullable()->after('name');
        });

        // Add back code column to commodities table
        Schema::table('commodities', function (Blueprint $table) {
            $table->string('code')->unique()->nullable()->after('name');
        });

        // Add back code column to units table
        Schema::table('units', function (Blueprint $table) {
            $table->string('code')->unique()->nullable()->after('name');
        });
    }
};