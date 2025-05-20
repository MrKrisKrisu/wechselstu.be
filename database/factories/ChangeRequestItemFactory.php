<?php

namespace Database\Factories;

use App\Models\ChangeRequestItem;
use App\Models\WorkOrder;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ChangeRequestItem>
 */
class ChangeRequestItemFactory extends Factory {
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array {
        return [
            'id'            => (string)Str::uuid(),
            'work_order_id' => WorkOrder::factory()->state(['type' => 'change_request']),
            'denomination'  => $this->faker->randomElement([1, 2, 5, 10, 20, 50, 100, 200]),
            'quantity'      => $this->faker->numberBetween(1, 10),
        ];
    }
}
