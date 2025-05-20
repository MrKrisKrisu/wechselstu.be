<?php

namespace Database\Factories;

use App\Models\CashRegister;
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
            'id'    => (string)Str::uuid(),
            'name'  => $this->faker->firstName . ' ' . $this->faker->randomElement(['Bar', 'Kitchen', 'Station']),
            'token' => (string)Str::uuid(),
        ];
    }
}
