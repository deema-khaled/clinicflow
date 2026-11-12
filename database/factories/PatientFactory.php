<?php

namespace Database\Factories;

use App\Models\Clinic;
use App\Models\Patient;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Patient>
 */
class PatientFactory extends Factory
{
    public function definition(): array
    {
        return [
            'clinic_id' => Clinic::factory(),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'phone' => fake()->unique()->e164PhoneNumber(),
            'email' => fake()->optional()->safeEmail(),
            'date_of_birth' => fake()->dateTimeBetween('-80 years', '-1 year'),
            'gender' => fake()->randomElement(['male', 'female']),
        ];
    }

    public function forClinic(Clinic $clinic): static
    {
        return $this->state(fn () => ['clinic_id' => $clinic->id]);
    }
}
