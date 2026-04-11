<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateInventoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('inventories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('inventory_tag')->nullable();
            $table->integer('model_id')->nullable();
            $table->string('serial')->nullable();
            $table->date('purchase_date')->nullable();
            $table->decimal('purchase_cost', 8, 2)->nullable();
            $table->string('order_number')->nullable();
            $table->integer('assigned_to')->nullable();
            $table->string('assigned_type')->nullable();
            $table->text('notes')->nullable();
            $table->integer('user_id')->nullable();
            $table->boolean('physical')->default(1);
            $table->integer('status_id')->nullable();
            $table->boolean('archived')->nullable();
            $table->string('accepted')->nullable();
            $table->integer('company_id')->nullable();
            $table->string('image')->nullable();
            $table->integer('location_id')->nullable();
            $table->integer('rtd_location_id')->nullable();
            $table->integer('supplier_id')->nullable();
            $table->integer('donor_id')->nullable();
            $table->integer('warranty_months')->nullable();
            $table->boolean('requestable')->default(0);
            $table->timestamp('last_checkout')->nullable();
            $table->timestamp('expected_checkin')->nullable();
            $table->boolean('byod')->default(0);
            $table->date('asset_eol_date')->nullable();
            $table->boolean('eol_explicit')->default(0);
            $table->timestamp('last_audit_date')->nullable();
            $table->date('next_audit_date')->nullable();
            $table->timestamp('last_checkin')->nullable();
            $table->integer('created_by')->nullable();
            $table->integer('checkout_counter')->default(0);
            $table->softDeletes();
            $table->timestamps();

            $table->index('inventory_tag');
            $table->index('serial');
            $table->index('model_id');
            $table->index('status_id');
            $table->index('assigned_to');
            $table->index('assigned_type');
            $table->index('location_id');
            $table->index('rtd_location_id');
            $table->index('company_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('inventories');
    }
}
