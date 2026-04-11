<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('goods_received_notes', function (Blueprint $table) {
            $table->id();
            $table->string('grn_number', 50)->unique();
            $table->unsignedInteger('supplier_id')->nullable();
            $table->unsignedInteger('received_by')->nullable();
            $table->date('received_date');
            $table->string('purchase_order_number', 100)->nullable();
            $table->enum('status', ['draft', 'posted', 'locked', 'cancelled'])->default('draft');
            $table->text('notes')->nullable();
            $table->string('inspection_status', 50)->nullable();
            $table->text('inspection_notes')->nullable();
            $table->unsignedInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->unsignedInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('grn_number');
            $table->index('status');
            $table->index('received_date');
            
            $table->foreign('supplier_id')->references('id')->on('suppliers')->onDelete('set null');
            $table->foreign('received_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
        });

        Schema::create('grn_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('grn_id');
            $table->string('item_type', 50);
            $table->string('item_name', 255);
            $table->text('item_description')->nullable();
            $table->unsignedInteger('category_id')->nullable();
            $table->unsignedInteger('manufacturer_id')->nullable();
            $table->unsignedInteger('supplier_id')->nullable();
            $table->integer('expected_quantity')->default(1);
            $table->integer('received_quantity')->default(0);
            $table->integer('accepted_quantity')->default(0);
            $table->integer('rejected_quantity')->default(0);
            $table->string('condition', 50)->nullable();
            $table->decimal('unit_cost', 12, 2)->default(0);
            $table->decimal('total_cost', 12, 2)->default(0);
            $table->string('serial_number', 100)->nullable();
            $table->string('batch_number', 100)->nullable();
            $table->date('expiry_date')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->unsignedInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['grn_id', 'item_type']);
            $table->index('batch_number');
            
            $table->foreign('grn_id')->references('id')->on('goods_received_notes')->onDelete('cascade');
            $table->foreign('category_id')->references('id')->on('categories')->onDelete('set null');
            $table->foreign('manufacturer_id')->references('id')->on('manufacturers')->onDelete('set null');
            $table->foreign('supplier_id')->references('id')->on('suppliers')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
        });

        Schema::create('grn_inspections', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('grn_id');
            $table->unsignedBigInteger('grn_item_id');
            $table->unsignedInteger('inspected_by')->nullable();
            $table->date('inspection_date');
            $table->enum('result', ['pending', 'accepted', 'rejected', 'partial'])->default('pending');
            $table->text('notes')->nullable();
            $table->string('discrepancy_type', 100)->nullable();
            $table->text('discrepancy_notes')->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->timestamps();

            $table->index(['grn_id', 'grn_item_id']);
            $table->index('result');
            
            $table->foreign('grn_id')->references('id')->on('goods_received_notes')->onDelete('cascade');
            $table->foreign('grn_item_id')->references('id')->on('grn_items')->onDelete('cascade');
            $table->foreign('inspected_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grn_inspections');
        Schema::dropIfExists('grn_items');
        Schema::dropIfExists('goods_received_notes');
    }
};
