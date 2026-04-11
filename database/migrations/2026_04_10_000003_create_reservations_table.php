<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->string('reservation_number', 50)->unique();
            $table->string('item_type', 50);
            $table->unsignedBigInteger('item_id');
            $table->integer('quantity')->default(1);
            $table->unsignedInteger('reserved_by')->nullable();
            $table->unsignedInteger('department_id')->nullable();
            $table->string('project_code', 100)->nullable();
            $table->text('notes')->nullable();
            $table->enum('status', ['active', 'fulfilled', 'cancelled', 'expired'])->default('active');
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('fulfilled_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->unsignedInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['item_type', 'item_id']);
            $table->index('status');
            $table->index('expires_at');
            $table->index('reserved_by');
            
            $table->foreign('reserved_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('department_id')->references('id')->on('departments')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservations');
    }
};
