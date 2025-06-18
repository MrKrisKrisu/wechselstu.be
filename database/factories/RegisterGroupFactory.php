<?php

namespace Database\Factories;

use App\Models\RegisterGroup;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RegisterGroup>
 */
class RegisterGroupFactory extends Factory {
    public function definition(): array {
        return [
            'name'     => $this->faker->unique()->word(),
            'password' => $this->faker->word,
        ];
    }
}
