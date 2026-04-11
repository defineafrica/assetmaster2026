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
        Schema::table('license_seats', function (Blueprint $table) {
            $table->integer('inventory_id')->nullable()->default(null)->after('asset_id');
            $table->index('inventory_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('license_seats', function (Blueprint $table) {
            $table->dropColumn('inventory_id');
        });
    }
};
