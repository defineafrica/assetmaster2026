<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventories', function (Blueprint $table) {
            $table->text('_snipeit_ram_3')->nullable();
            $table->text('_snipeit_cpu_4')->nullable();
            $table->text('_snipeit_mac_address_5')->nullable();
            $table->text('_snipeit_test_encrypted_6')->nullable();
            $table->text('_snipeit_test_checkbox_7')->nullable();
            $table->text('_snipeit_test_radio_8')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('inventories', function (Blueprint $table) {
            $table->dropColumn([
                '_snipeit_ram_3',
                '_snipeit_cpu_4',
                '_snipeit_mac_address_5',
                '_snipeit_test_encrypted_6',
                '_snipeit_test_checkbox_7',
                '_snipeit_test_radio_8',
            ]);
        });
    }
};
