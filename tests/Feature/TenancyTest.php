<?php

use App\Enums\Role;
use App\Models\Clinic;
use App\Models\Patient;
use App\Models\User;

beforeEach(function () {
    $this->clinicA = Clinic::factory()->create();
    $this->clinicB = Clinic::factory()->create();

    Patient::factory(3)->forClinic($this->clinicA)->create();
    Patient::factory(2)->forClinic($this->clinicB)->create();

    $this->userA = User::factory()->forClinic($this->clinicA)->create();
});

test('users only see patients from their own clinic', function () {
    $this->actingAs($this->userA);

    expect(Patient::count())->toBe(3)
        ->and(Patient::pluck('clinic_id')->unique()->all())->toBe([$this->clinicA->id]);
});

test('a patient from another clinic cannot be found by id', function () {
    $foreign = Patient::withoutGlobalScopes()->where('clinic_id', $this->clinicB->id)->first();

    $this->actingAs($this->userA);

    expect(Patient::find($foreign->id))->toBeNull();
});

test('new records are assigned to the signed-in user\'s clinic', function () {
    $this->actingAs($this->userA);

    $patient = Patient::create([
        'first_name' => 'Sara',
        'last_name' => 'Haddad',
        'phone' => '+962790000000',
    ]);

    expect($patient->clinic_id)->toBe($this->clinicA->id);
});

test('a clinic_id sent by the client cannot override the tenant', function () {
    $this->actingAs($this->userA);

    $patient = new Patient([
        'first_name' => 'Evil',
        'last_name' => 'Insert',
        'phone' => '+962790000001',
    ]);
    $patient->clinic_id = $this->clinicB->id; // even set directly
    $patient->save();

    expect($patient->fresh()->clinic_id)->toBe($this->clinicA->id)
        ->and(Patient::withoutGlobalScopes()->where('clinic_id', $this->clinicB->id)->count())->toBe(2);
});

test('clinic_id is not mass assignable', function () {
    expect((new Patient)->isFillable('clinic_id'))->toBeFalse();
});

test('queries are unscoped without a signed-in user', function () {
    expect(Patient::count())->toBe(5);
});

test('roles expose their permissions', function () {
    expect(Role::Owner->canManageClinic())->toBeTrue()
        ->and(Role::Doctor->canManageClinic())->toBeFalse()
        ->and(Role::Doctor->canAccessMedicalRecords())->toBeTrue()
        ->and(Role::Receptionist->canAccessMedicalRecords())->toBeFalse();

    $doctor = User::factory()->forClinic($this->clinicA)->role(Role::Doctor)->create();

    expect($doctor->hasRole(Role::Doctor, Role::Owner))->toBeTrue()
        ->and($doctor->hasRole(Role::Receptionist))->toBeFalse();
});
