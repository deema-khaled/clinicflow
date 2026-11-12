<?php

namespace Database\Factories;

use App\Enums\AppointmentStatus;
use App\Enums\Role;
use App\Models\Appointment;
use App\Models\Clinic;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Appointment>
 */
class AppointmentFactory extends Factory
{
    public function definition(): array
    {
        $start = now()->addDays(fake()->numberBetween(0, 6))->setTime(fake()->numberBetween(9, 16), fake()->randomElement([0, 30]));

        return [
            'clinic_id' => Clinic::factory(),
            'patient_id' => fn (array $attributes) => Patient::factory()->state(['clinic_id' => $attributes['clinic_id']]),
            'doctor_id' => fn (array $attributes) => User::factory()->role(Role::Doctor)->state(['clinic_id' => $attributes['clinic_id']]),
            'starts_at' => $start,
            'ends_at' => (clone $start)->addMinutes(30),
            'status' => AppointmentStatus::Scheduled,
            'reason' => fake()->randomElement(['Check-up', 'Follow-up', 'Consultation', 'Cleaning', 'Vaccination', 'Lab results']),
        ];
    }

    public function at(string $start, int $minutes = 30): static
    {
        return $this->state(fn () => [
            'starts_at' => $start,
            'ends_at' => now()->parse($start)->addMinutes($minutes),
        ]);
    }

    public function status(AppointmentStatus $status): static
    {
        return $this->state(fn () => ['status' => $status]);
    }
}
