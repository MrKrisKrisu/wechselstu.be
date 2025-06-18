<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up(): void {
        Schema::table('cash_registers', function(Blueprint $table) {
            $table->string('register_group_id')->nullable()->after('id');

            $table->foreign('register_group_id')->references('id')->on('register_groups');
        });
    }

    public function down(): void {
        Schema::table('cash_registers', function(Blueprint $table) {
            $table->dropColumn('register_group_id');
        });
    }
};
