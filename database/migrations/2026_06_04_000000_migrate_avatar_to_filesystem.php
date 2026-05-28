<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('avatar_path')->nullable()->after('member_appearance');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('avatar');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('avatar_path');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->binary('avatar')->nullable()->after('member_appearance');
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE users MODIFY avatar MEDIUMBLOB NULL');
        }
    }
};
