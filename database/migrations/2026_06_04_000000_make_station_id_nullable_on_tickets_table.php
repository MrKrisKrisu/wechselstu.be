<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropForeign(['station_id']);
            $table->foreignUuid('station_id')->nullable()->change();
            $table->foreign('station_id')->references('id')->on('stations')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropForeign(['station_id']);
            $table->foreignUuid('station_id')->nullable(false)->change();
            $table->foreign('station_id')->references('id')->on('stations')->cascadeOnDelete();
        });
    }
};
