<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_thresholds', function (Blueprint $table) {
            $table->id();
            $table->string('item_type', 50);
            $table->unsignedBigInteger('item_id');
            $table->integer('min_quantity')->default(0);
            $table->integer('reorder_quantity')->default(0);
            $table->string('alert_email')->nullable();
            $table->boolean('send_sms')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('created_by')->nullable();
            $table->unsignedInteger('updated_by')->nullable();
            $table->timestamps();
            
            $table->index(['item_type', 'item_id']);
            $table->index('is_active');
            
            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('updated_by')->references('id')->on('users');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_thresholds');
    }
};
