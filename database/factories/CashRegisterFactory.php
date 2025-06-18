<?php

namespace Database\Factories;

use App\Models\CashRegister;
use App\Models\RegisterGroup;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<CashRegister>
 */
class CashRegisterFactory extends Factory {
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array {
        return [
            'id'                => (string)Str::uuid(),
            'register_group_id' => $this->faker->boolean(0.3) ? RegisterGroup::all()->random()->id : null,
            'name'              => $this->faker->firstName . ' ' . $this->faker->randomElement(['Bar', 'Kitchen', 'Station']),
            'token'             => (string)Str::uuid(),
        ];
    }
}
