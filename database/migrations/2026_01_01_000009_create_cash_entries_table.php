<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cash_entries', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->integer('amount_cents');
            $table->text('description')->nullable();
            $table->foreignUuid('created_by')->constrained('users');
            $table->foreignUuid('ticket_id')->nullable()->constrained('tickets')->nullOnDelete();
            $table->foreignUuid('counterpart_station_id')->nullable()->constrained('stations')->nullOnDelete();
            $table->foreignUuid('reversed_by_entry_id')->nullable();
            $table->timestamp('reversed_at')->nullable();
            $table->timestamps();

            $table->foreign('reversed_by_entry_id')->references('id')->on('cash_entries')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_entries');
    }
};
