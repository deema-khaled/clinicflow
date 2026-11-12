<?php

use App\Enums\Role;
use App\Models\Clinic;
use App\Models\Patient;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->clinic = Clinic::factory()->create();
    $this->owner = User::factory()->forClinic($this->clinic)->create();
    $this->doctor = User::factory()->forClinic($this->clinic)->role(Role::Doctor)->create();
    $this->receptionist = User::factory()->forClinic($this->clinic)->role(Role::Receptionist)->create();

    $this->otherClinic = Clinic::factory()->create();
    $this->foreignPatient = Patient::factory()->forClinic($this->otherClinic)->create();
});

function validPatient(array $overrides = []): array
{
    return array_merge([
        'first_name' => 'Sara',
        'last_name' => 'Haddad',
        'phone' => '+962 79 123 4567',
        'email' => 'sara@example.com',
        'date_of_birth' => '1990-05-20',
        'gender' => 'female',
        'notes' => 'Allergic to penicillin',
    ], $overrides);
}

// --- Access ---------------------------------------------------------------

test('guests are redirected to login', function () {
    $this->get(route('patients.index'))->assertRedirect(route('login'));
});

test('the list only shows patients from the user\'s clinic', function () {
    Patient::factory(3)->forClinic($this->clinic)->create();

    $this->actingAs($this->receptionist)
        ->get(route('patients.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('patients/Index')
            ->has('patients.data', 3)
            ->where('patients.total', 3)
            ->where('can.delete', false)
        );
});

test('the list can be searched by name or phone', function () {
    Patient::factory()->forClinic($this->clinic)->create(['first_name' => 'Omar', 'phone' => '+962700000001']);
    Patient::factory()->forClinic($this->clinic)->create(['first_name' => 'Lina', 'phone' => '+962700000002']);

    $this->actingAs($this->owner)
        ->get(route('patients.index', ['search' => 'oma']))
        ->assertInertia(fn (Assert $page) => $page
            ->has('patients.data', 1)
            ->where('patients.data.0.full_name', fn ($name) => str_starts_with($name, 'Omar'))
            ->where('filters.search', 'oma')
        );

    $this->actingAs($this->owner)
        ->get(route('patients.index', ['search' => '0000002']))
        ->assertInertia(fn (Assert $page) => $page->has('patients.data', 1));
});

test('the list is paginated', function () {
    Patient::factory(15)->forClinic($this->clinic)->create();

    $this->actingAs($this->owner)
        ->get(route('patients.index', ['page' => 2]))
        ->assertInertia(fn (Assert $page) => $page
            ->has('patients.data', 5)
            ->where('patients.current_page', 2)
        );
});

test('a patient from another clinic returns 404', function (string $method, string $route) {
    $this->actingAs($this->owner)
        ->{$method}(route($route, $this->foreignPatient), validPatient())
        ->assertNotFound();

    expect($this->foreignPatient->fresh()->trashed())->toBeFalse();
})->with([
    'edit' => ['get', 'patients.edit'],
    'update' => ['put', 'patients.update'],
    'delete' => ['delete', 'patients.destroy'],
]);

// --- Create & update -------------------------------------------------------

test('staff can add a patient to their clinic', function () {
    $this->actingAs($this->receptionist)
        ->post(route('patients.store'), validPatient())
        ->assertRedirect(route('patients.index'))
        ->assertSessionHas('success');

    $patient = Patient::withoutGlobalScopes()->firstWhere('first_name', 'Sara');

    expect($patient->clinic_id)->toBe($this->clinic->id)
        ->and($patient->date_of_birth->toDateString())->toBe('1990-05-20');
});

test('a patient can be updated', function () {
    $patient = Patient::factory()->forClinic($this->clinic)->create();

    $this->actingAs($this->doctor)
        ->put(route('patients.update', $patient), validPatient(['first_name' => 'Updated']))
        ->assertRedirect(route('patients.index'));

    expect($patient->fresh()->first_name)->toBe('Updated');
});

test('validation rejects bad input', function (array $data, string $field) {
    $this->actingAs($this->owner)
        ->post(route('patients.store'), validPatient($data))
        ->assertSessionHasErrors($field);
})->with([
    'missing first name' => [['first_name' => ''], 'first_name'],
    'missing phone' => [['phone' => ''], 'phone'],
    'letters in phone' => [['phone' => 'call me maybe'], 'phone'],
    'bad email' => [['email' => 'not-an-email'], 'email'],
    'birth date in future' => [['date_of_birth' => now()->addDay()->toDateString()], 'date_of_birth'],
    'unknown gender' => [['gender' => 'robot'], 'gender'],
]);

test('phone numbers are unique within a clinic but not across clinics', function () {
    Patient::factory()->forClinic($this->clinic)->create(['phone' => '+962790000000']);

    $this->actingAs($this->owner)
        ->post(route('patients.store'), validPatient(['phone' => '+962790000000']))
        ->assertSessionHasErrors('phone');

    // Same number in another clinic is fine.
    $otherOwner = User::factory()->forClinic($this->otherClinic)->create();
    $this->actingAs($otherOwner)
        ->post(route('patients.store'), validPatient(['phone' => '+962790000000']))
        ->assertSessionHasNoErrors();
});

test('updating a patient keeps their own phone number valid', function () {
    $patient = Patient::factory()->forClinic($this->clinic)->create(['phone' => '+962790000000']);

    $this->actingAs($this->owner)
        ->put(route('patients.update', $patient), validPatient(['phone' => '+962790000000']))
        ->assertSessionHasNoErrors();
});

// --- Medical notes ----------------------------------------------------------

test('receptionists cannot read medical notes', function () {
    $patient = Patient::factory()->forClinic($this->clinic)->create(['notes' => 'Secret diagnosis']);

    $this->actingAs($this->receptionist)
        ->get(route('patients.edit', $patient))
        ->assertInertia(fn (Assert $page) => $page
            ->where('patient.notes', null)
            ->where('canEditNotes', false)
        );

    $this->actingAs($this->doctor)
        ->get(route('patients.edit', $patient))
        ->assertInertia(fn (Assert $page) => $page
            ->where('patient.notes', 'Secret diagnosis')
            ->where('canEditNotes', true)
        );
});

test('receptionists cannot write medical notes', function () {
    $patient = Patient::factory()->forClinic($this->clinic)->create(['notes' => 'Original']);

    $this->actingAs($this->receptionist)
        ->put(route('patients.update', $patient), validPatient(['notes' => 'Overwritten']))
        ->assertSessionHasNoErrors();

    expect($patient->fresh()->notes)->toBe('Original');
});

// --- Delete ------------------------------------------------------------------

test('only owners can delete patients', function () {
    $patient = Patient::factory()->forClinic($this->clinic)->create();

    $this->actingAs($this->doctor)->delete(route('patients.destroy', $patient))->assertForbidden();
    $this->actingAs($this->receptionist)->delete(route('patients.destroy', $patient))->assertForbidden();

    $this->actingAs($this->owner)
        ->delete(route('patients.destroy', $patient))
        ->assertRedirect(route('patients.index'));

    expect($patient->fresh()->trashed())->toBeTrue();
});
