<?php

use App\Enums\AppointmentStatus;
use App\Enums\Role;
use App\Models\Appointment;
use App\Models\Clinic;
use App\Models\Patient;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->travelTo('2026-11-11 08:00'); // a Wednesday

    $this->clinic = Clinic::factory()->create();
    $this->owner = User::factory()->forClinic($this->clinic)->create();
    $this->doctor = User::factory()->forClinic($this->clinic)->role(Role::Doctor)->create();
    $this->receptionist = User::factory()->forClinic($this->clinic)->role(Role::Receptionist)->create();
    $this->patient = Patient::factory()->forClinic($this->clinic)->create();
});

function booking(array $overrides = []): array
{
    return array_merge([
        'patient_id' => test()->patient->id,
        'doctor_id' => test()->doctor->id,
        'date' => '2026-11-12',
        'start_time' => '10:00',
        'duration' => 30,
        'reason' => 'Check-up',
    ], $overrides);
}

function existingAppointment(string $start = '2026-11-12 10:00', int $minutes = 30, array $attributes = []): Appointment
{
    return Appointment::factory()->at($start, $minutes)->create([
        'clinic_id' => test()->clinic->id,
        'patient_id' => test()->patient->id,
        'doctor_id' => test()->doctor->id,
        ...$attributes,
    ]);
}

// --- Calendar -------------------------------------------------------------------

test('the calendar shows the current week starting on Saturday', function () {
    existingAppointment('2026-11-12 10:00');
    existingAppointment('2026-11-20 10:00'); // next week, hidden

    $this->actingAs($this->receptionist)
        ->get(route('appointments.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('appointments/Index')
            ->has('days', 7)
            ->where('days.0.date', '2026-11-07')
            ->where('days.5.date', '2026-11-12')
            ->has('days.5.appointments', 1)
            ->where('days.5.appointments.0.start', '10:00')
            ->where('days.4.is_today', true)
        );
});

test('the calendar can be filtered by doctor', function () {
    $other = User::factory()->forClinic($this->clinic)->role(Role::Doctor)->create();
    existingAppointment('2026-11-12 10:00');
    existingAppointment('2026-11-12 10:00', 30, ['doctor_id' => $other->id]);

    $this->actingAs($this->owner)
        ->get(route('appointments.index', ['doctor' => $other->id]))
        ->assertInertia(fn (Assert $page) => $page
            ->has('days.5.appointments', 1)
            ->where('days.5.appointments.0.doctor.id', $other->id)
        );
});

test('an invalid week falls back to the current week', function () {
    $this->actingAs($this->owner)
        ->get(route('appointments.index', ['week' => 'not-a-date']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('week.start', '2026-11-07'));
});

test('appointments from other clinics are never shown', function () {
    Appointment::factory()->at('2026-11-12 10:00')->create(); // its own clinic

    $this->actingAs($this->owner)
        ->get(route('appointments.index'))
        ->assertInertia(fn (Assert $page) => $page->has('days.5.appointments', 0));
});

// --- Booking --------------------------------------------------------------------

test('staff can book an appointment', function () {
    $this->actingAs($this->receptionist)
        ->post(route('appointments.store'), booking())
        ->assertRedirect(route('appointments.index', ['week' => '2026-11-12']))
        ->assertSessionHas('success');

    $appointment = Appointment::withoutGlobalScopes()->sole();

    expect($appointment->clinic_id)->toBe($this->clinic->id)
        ->and($appointment->starts_at->format('Y-m-d H:i'))->toBe('2026-11-12 10:00')
        ->and($appointment->ends_at->format('H:i'))->toBe('10:30')
        ->and($appointment->status)->toBe(AppointmentStatus::Scheduled);
});

test('a doctor cannot be double-booked', function (string $start, int $duration) {
    existingAppointment('2026-11-12 10:00', 30); // 10:00–10:30

    $this->actingAs($this->owner)
        ->post(route('appointments.store'), booking(['start_time' => $start, 'duration' => $duration]))
        ->assertSessionHasErrors(['start_time' => 'This doctor is already booked 10:00–10:30 ('.$this->patient->full_name.').']);

    expect(Appointment::count())->toBe(1);
})->with([
    'same slot' => ['10:00', 30],
    'starts inside' => ['10:15', 30],
    'ends inside' => ['09:45', 30],
    'surrounds it' => ['09:30', 90],
]);

test('back-to-back appointments are allowed', function (string $start) {
    existingAppointment('2026-11-12 10:00', 30);

    $this->actingAs($this->owner)
        ->post(route('appointments.store'), booking(['start_time' => $start, 'duration' => 30]))
        ->assertSessionHasNoErrors();
})->with(['right before' => '09:30', 'right after' => '10:30']);

test('a cancelled appointment frees its slot', function () {
    existingAppointment('2026-11-12 10:00', 30, ['status' => AppointmentStatus::Cancelled]);

    $this->actingAs($this->owner)
        ->post(route('appointments.store'), booking())
        ->assertSessionHasNoErrors();
});

test('another doctor can take the same slot', function () {
    existingAppointment('2026-11-12 10:00');
    $other = User::factory()->forClinic($this->clinic)->role(Role::Doctor)->create();

    $this->actingAs($this->owner)
        ->post(route('appointments.store'), booking(['doctor_id' => $other->id]))
        ->assertSessionHasNoErrors();
});

test('patients and doctors must belong to the clinic', function () {
    $foreignPatient = Patient::factory()->create();
    $foreignDoctor = User::factory()->role(Role::Doctor)->create();

    $this->actingAs($this->owner)
        ->post(route('appointments.store'), booking(['patient_id' => $foreignPatient->id, 'doctor_id' => $foreignDoctor->id]))
        ->assertSessionHasErrors(['patient_id', 'doctor_id']);
});

test('receptionists cannot be booked as doctors', function () {
    $this->actingAs($this->owner)
        ->post(route('appointments.store'), booking(['doctor_id' => $this->receptionist->id]))
        ->assertSessionHasErrors('doctor_id');
});

test('validation rejects bad input', function (array $data, string $field) {
    $this->actingAs($this->owner)
        ->post(route('appointments.store'), booking($data))
        ->assertSessionHasErrors($field);
})->with([
    'bad date' => [['date' => '12/11/2026'], 'date'],
    'bad time' => [['start_time' => '25:00'], 'start_time'],
    'odd duration' => [['duration' => 17], 'duration'],
]);

// --- Editing --------------------------------------------------------------------

test('an appointment can be rescheduled without clashing with itself', function () {
    $appointment = existingAppointment('2026-11-12 10:00', 30);

    $this->actingAs($this->owner)
        ->put(route('appointments.update', $appointment), booking(['start_time' => '10:15', 'status' => 'scheduled']))
        ->assertSessionHasNoErrors();

    expect($appointment->fresh()->starts_at->format('H:i'))->toBe('10:15');
});

test('the status can be changed from the calendar', function () {
    $appointment = existingAppointment();

    $this->actingAs($this->receptionist)
        ->patch(route('appointments.status', $appointment), ['status' => 'completed'])
        ->assertSessionHas('success');

    expect($appointment->fresh()->status)->toBe(AppointmentStatus::Completed);
});

test('restoring a cancelled appointment is blocked if the slot was taken', function () {
    $cancelled = existingAppointment('2026-11-12 10:00', 30, ['status' => AppointmentStatus::Cancelled]);
    existingAppointment('2026-11-12 10:00', 30);

    $this->actingAs($this->owner)
        ->patch(route('appointments.status', $cancelled), ['status' => 'scheduled'])
        ->assertSessionHasErrors('status');

    expect($cancelled->fresh()->status)->toBe(AppointmentStatus::Cancelled);
});

test('another clinic\'s appointment returns 404', function () {
    $foreign = Appointment::factory()->create();

    $this->actingAs($this->owner)->get(route('appointments.edit', $foreign))->assertNotFound();
    $this->actingAs($this->owner)->patch(route('appointments.status', $foreign), ['status' => 'cancelled'])->assertNotFound();
});

test('only owners can delete appointments', function () {
    $appointment = existingAppointment();

    $this->actingAs($this->doctor)->delete(route('appointments.destroy', $appointment))->assertForbidden();

    $this->actingAs($this->owner)->delete(route('appointments.destroy', $appointment))->assertRedirect();
    expect(Appointment::count())->toBe(0);
});
