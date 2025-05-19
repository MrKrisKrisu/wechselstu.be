<?php

namespace Database\Factories;

use App\Enum\WorkOrderStatus;
use App\Models\CashRegister;
use App\Models\WorkOrder;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<WorkOrder>
 */
class WorkOrderFactory extends Factory {
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array {
        return [
            'id'               => (string)Str::uuid(),
            'cash_register_id' => CashRegister::factory(),
            'type'             => $this->faker->randomElement(['overflow', 'change_request']),
            'status'           => WorkOrderStatus::PENDING,
            'created_by'       => $this->faker->optional()->name,
            'notes'            => $this->faker->optional()->sentence,
        ];
    }
}
