<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'member_token')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('member_token', 32)->unique()->nullable()->after('remember_token');
                $table->text('member_bio')->nullable()->after('member_token');
            });
        }

        DB::table('users')->whereNull('member_token')->orderBy('id')->each(function ($user) {
            DB::table('users')
                ->where('id', $user->id)
                ->update(['member_token' => Str::random(32)]);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('member_token', 32)->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['member_token', 'member_bio']);
        });
    }
};
