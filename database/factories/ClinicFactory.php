<?php

namespace Database\Factories;

use App\Models\Clinic;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Clinic>
 */
class ClinicFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->company().' Clinic',
            'phone' => fake()->phoneNumber(),
            'address' => fake()->address(),
            'timezone' => 'UTC',
        ];
    }
}
