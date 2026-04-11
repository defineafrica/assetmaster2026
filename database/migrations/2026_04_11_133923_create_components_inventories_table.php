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
        Schema::create('components_inventories', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id')->nullable()->default(null);
            $table->integer('assigned_qty')->nullable()->default(1);
            $table->integer('component_id')->nullable()->default(null);
            $table->integer('inventory_id')->nullable()->default(null);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('components_inventories');
    }
};
