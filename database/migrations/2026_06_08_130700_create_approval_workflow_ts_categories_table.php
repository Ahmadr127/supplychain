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
        Schema::create('approval_workflow_ts_categories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('approval_workflow_id');
            $table->unsignedBigInteger('item_category_id');
            $table->timestamps();

            $table->foreign('approval_workflow_id', 'ts_cat_workflow_fk')->references('id')->on('approval_workflows')->onDelete('cascade');
            $table->foreign('item_category_id', 'ts_cat_item_cat_fk')->references('id')->on('item_categories')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('approval_workflow_ts_categories');
    }
};
