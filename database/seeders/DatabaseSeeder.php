<?php

namespace Database\Seeders;

use App\Models\Station;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(['name' => 'dev'], [
            'password' => Hash::make('password'),
        ]);

        User::firstOrCreate(['name' => 'dev2'], [
            'password' => Hash::make('password'),
        ]);

        User::firstOrCreate(['name' => 'dev3'], [
            'password' => Hash::make('password'),
        ]);

        Station::firstOrCreate(['name' => 'Tschunk Kasse', 'location' => 'Bar']);
        Station::firstOrCreate(['name' => 'Snack Kasse', 'location' => 'Bar']);
        Station::firstOrCreate(['name' => 'Kasse 1', 'location' => 'Außenbar']);
        Station::firstOrCreate(['name' => 'Kasse 1', 'location' => 'Merch']);
        Station::firstOrCreate(['name' => 'Kasse 2', 'location' => 'Merch']);
    }
}
