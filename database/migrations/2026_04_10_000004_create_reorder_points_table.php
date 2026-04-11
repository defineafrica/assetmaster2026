<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reorder_points', function (Blueprint $table) {
            $table->id();
            $table->string('item_type', 50);
            $table->unsignedBigInteger('item_id');
            $table->unsignedInteger('category_id')->nullable();
            $table->integer('reorder_point')->default(0);
            $table->integer('safety_stock')->default(0);
            $table->integer('reorder_quantity')->default(0);
            $table->unsignedInteger('preferred_supplier_id')->nullable();
            $table->boolean('auto_replenish')->default(false);
            $table->integer('lead_time_days')->default(7);
            $table->integer('alert_threshold_days')->default(3);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('created_by')->nullable();
            $table->unsignedInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['item_type', 'item_id']);
            $table->index('is_active');
            $table->index('category_id');
            
            $table->foreign('category_id')->references('id')->on('categories')->onDelete('set null');
            $table->foreign('preferred_supplier_id')->references('id')->on('suppliers')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reorder_points');
    }
};
