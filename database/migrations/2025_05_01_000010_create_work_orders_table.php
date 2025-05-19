<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void {
        Schema::create('work_orders', function(Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('cash_register_id');
            $table->enum('type', ['overflow', 'change_request']);
            $table->enum('status', ['pending', 'in_progress', 'done'])->default('pending');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('cash_register_id')
                  ->references('id')->on('cash_registers')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {
        Schema::dropIfExists('work_orders');
    }
};
