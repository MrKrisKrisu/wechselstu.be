<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void {
        Schema::create('change_request_items', function(Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('work_order_id');
            $table->unsignedInteger('denomination');
            $table->unsignedInteger('quantity');
            $table->timestamps();

            $table->foreign('work_order_id')
                  ->references('id')->on('work_orders')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {
        Schema::dropIfExists('change_request_items');
    }
};
