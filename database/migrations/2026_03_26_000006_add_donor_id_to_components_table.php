<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddDonorIdToComponentsTable extends Migration
{
    public function up()
    {
        Schema::table('components', function (Blueprint $table) {
            $table->unsignedInteger('donor_id')->nullable()->after('company_id');
            $table->foreign('donor_id')->references('id')->on('donors')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::table('components', function (Blueprint $table) {
            $table->dropForeign(['donor_id']);
            $table->dropColumn('donor_id');
        });
    }
}