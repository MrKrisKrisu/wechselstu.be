<?php

namespace Database\Seeders;

use App\Models\CashRegister;
use App\Models\ChangeRequestItem;
use App\Models\User;
use App\Models\WorkOrder;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder {
    public function run(): void {
        User::factory()->create([
                                    'name'     => 'John Doe',
                                    'email'    => 'dev@dev.de',
                                    'password' => Hash::make('password'),
                                ]);

        CashRegister::factory()->count(10)->create();

        for($i = 0; $i <= 20; $i++) {
            WorkOrder::factory(['cash_register_id' => CashRegister::all()->random()->id])->create();
        }

        $changeRequestOrders = WorkOrder::where('type', 'change_request')->get();
        foreach($changeRequestOrders as $changeRequestOrder) {
            ChangeRequestItem::factory(['work_order_id' => $changeRequestOrder->id])->create();
        }
    }
}
