<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class CustomerFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'phone' => fake()->optional(0.8)->phoneNumber(),
            'email' => fake()->optional(0.8)->safeEmail(),
            'created_at' => fake()->dateTimeBetween('-1 year', 'now'),
        ];
    }
}
