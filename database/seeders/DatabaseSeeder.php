<?php

namespace Database\Seeders;

use App\Enums\AppointmentStatus;
use App\Enums\Role;
use App\Models\Appointment;
use App\Models\Clinic;
use App\Models\Patient;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

class DatabaseSeeder extends Seeder
{
    /**
     * Two demo clinics, so tenant isolation is easy to see in the browser.
     * Every account's password is "password".
     */
    public function run(): void
    {
        $demo = Clinic::factory()->create(['name' => 'Demo Dental Clinic']);

        User::factory()->forClinic($demo)->create(['name' => 'Demo Owner', 'email' => 'owner@demo.test']);
        $doctors = collect([
            User::factory()->forClinic($demo)->role(Role::Doctor)->create(['name' => 'Dr. Rana Haddad', 'email' => 'doctor@demo.test']),
            User::factory()->forClinic($demo)->role(Role::Doctor)->create(['name' => 'Dr. Sami Khoury', 'email' => 'doctor2@demo.test']),
        ]);
        User::factory()->forClinic($demo)->role(Role::Receptionist)->create(['name' => 'Demo Reception', 'email' => 'reception@demo.test']);

        $patients = Patient::factory(25)->forClinic($demo)->create();
        $this->seedSchedule($demo, $doctors, $patients);

        $other = Clinic::factory()->create(['name' => 'Other Family Clinic']);
        User::factory()->forClinic($other)->create(['name' => 'Other Owner', 'email' => 'owner@other.test']);
        $otherDoctor = User::factory()->forClinic($other)->role(Role::Doctor)->create(['name' => 'Dr. Other']);
        $this->seedSchedule($other, collect([$otherDoctor]), Patient::factory(10)->forClinic($other)->create());
    }

    /**
     * Last week, this week and next week: a realistic, non-overlapping
     * schedule. Past visits are completed (some no-shows), future ones scheduled.
     *
     * @param  Collection<int, User>  $doctors
     * @param  Collection<int, Patient>  $patients
     */
    private function seedSchedule(Clinic $clinic, Collection $doctors, Collection $patients): void
    {
        $reasons = ['Check-up', 'Follow-up', 'Consultation', 'Cleaning', 'Filling', 'X-ray review', 'Whitening', 'Lab results'];
        $start = CarbonImmutable::today()->startOfWeek(CarbonImmutable::SATURDAY)->subWeek();
        $now = now();

        for ($day = 0; $day < 21; $day++) {
            $date = $start->addDays($day);

            if ($date->isFriday()) {
                continue; // weekend
            }

            foreach ($doctors as $doctor) {
                $slot = $date->setTime(9, 0);

                while ($slot->hour < 16) {
                    $minutes = fake()->randomElement([30, 30, 30, 45, 60]);

                    if (fake()->boolean(55)) {
                        $isPast = $slot->lt($now);
                        $status = match (true) {
                            fake()->boolean(7) => AppointmentStatus::Cancelled,
                            $isPast => fake()->boolean(88) ? AppointmentStatus::Completed : AppointmentStatus::NoShow,
                            default => AppointmentStatus::Scheduled,
                        };

                        Appointment::factory()->create([
                            'clinic_id' => $clinic->id,
                            'patient_id' => $patients->random()->id,
                            'doctor_id' => $doctor->id,
                            'starts_at' => $slot,
                            'ends_at' => $slot->addMinutes($minutes),
                            'status' => $status,
                            'reason' => fake()->randomElement($reasons),
                        ]);
                    }

                    $slot = $slot->addMinutes($minutes + fake()->randomElement([0, 0, 15, 30]));
                }
            }
        }
    }
}
