<?php

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected to the login page', function () {
    $this->get('/dashboard')->assertRedirect('/login');
});

test('the dashboard shows this clinic\'s numbers only', function () {
    $this->travelTo('2026-11-11 08:00'); // Wednesday

    $user = User::factory()->create();
    $clinic = $user->clinic;
    $patient = Patient::factory()->forClinic($clinic)->create();
    $make = fn (string $at, AppointmentStatus $status) => Appointment::factory()->at($at)->status($status)
        ->create(['clinic_id' => $clinic->id, 'patient_id' => $patient->id, 'doctor_id' => $user->id]);

    $make('2026-11-11 09:00', AppointmentStatus::Scheduled);
    $make('2026-11-11 11:00', AppointmentStatus::Cancelled);
    $make('2026-11-09 09:00', AppointmentStatus::Completed);
    $make('2026-11-08 09:00', AppointmentStatus::Completed);
    $make('2026-11-08 10:00', AppointmentStatus::Completed);
    $make('2026-11-08 11:00', AppointmentStatus::NoShow);

    // Noise from another clinic.
    Appointment::factory()->at('2026-11-11 09:00')->create();

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->where('stats.patients', 1)
            ->where('stats.today', 1)
            ->where('stats.today_remaining', 1)
            ->where('stats.this_week', 5)
            ->where('stats.completion_rate', 75)
            ->has('todayAppointments', 2)
            ->has('week', 7)
            ->where('week.4.count', 1)
            ->where('week.4.is_today', true)
        );
});

test('a brand-new clinic gets an empty dashboard', function () {
    $this->actingAs(User::factory()->create())
        ->get('/dashboard')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('stats.patients', 0)
            ->where('stats.completion_rate', null)
            ->has('todayAppointments', 0)
        );
});
