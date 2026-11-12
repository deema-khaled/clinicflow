<?php

use App\Enums\Role;
use App\Models\Clinic;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->clinic = Clinic::factory()->create();
    $this->owner = User::factory()->forClinic($this->clinic)->create();
    $this->doctor = User::factory()->forClinic($this->clinic)->role(Role::Doctor)->create();
});

test('only owners can open the staff page', function () {
    $this->actingAs($this->doctor)->get(route('staff.index'))->assertForbidden();

    $this->actingAs($this->owner)
        ->get(route('staff.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('staff/Index')
            ->has('staff', 2)
            ->where('staff.0.role', 'owner')
            ->where('staff.0.is_me', true)
        );
});

test('staff from other clinics are not listed', function () {
    User::factory()->create(); // other clinic

    $this->actingAs($this->owner)
        ->get(route('staff.index'))
        ->assertInertia(fn (Assert $page) => $page->has('staff', 2));
});

test('owners can add a doctor who can then sign in', function () {
    $this->actingAs($this->owner)
        ->post(route('staff.store'), [
            'name' => 'Dr. New',
            'email' => 'new@clinic.test',
            'role' => 'doctor',
            'password' => 'secret-password',
        ])
        ->assertSessionHasNoErrors();

    $member = User::firstWhere('email', 'new@clinic.test');

    expect($member->clinic_id)->toBe($this->clinic->id)
        ->and($member->role)->toBe(Role::Doctor)
        ->and($member->email_verified_at)->not->toBeNull();

    auth()->logout();
    $this->post('/login', ['email' => 'new@clinic.test', 'password' => 'secret-password']);
    $this->assertAuthenticatedAs($member);
});

test('a second owner cannot be created from the form', function () {
    $this->actingAs($this->owner)
        ->post(route('staff.store'), ['name' => 'X', 'email' => 'x@clinic.test', 'role' => 'owner', 'password' => 'secret-password'])
        ->assertSessionHasErrors('role');
});

test('owners can change a role and remove a member', function () {
    $this->actingAs($this->owner)
        ->patch(route('staff.update', $this->doctor), ['role' => 'receptionist'])
        ->assertSessionHasNoErrors();
    expect($this->doctor->fresh()->role)->toBe(Role::Receptionist);

    $this->actingAs($this->owner)->delete(route('staff.destroy', $this->doctor))->assertRedirect();
    expect(User::find($this->doctor->id))->toBeNull();
});

test('owners cannot change or remove themselves', function () {
    $this->actingAs($this->owner)->patch(route('staff.update', $this->owner), ['role' => 'doctor'])->assertForbidden();
    $this->actingAs($this->owner)->delete(route('staff.destroy', $this->owner))->assertForbidden();
});

test('staff of another clinic cannot be touched', function () {
    $foreign = User::factory()->role(Role::Doctor)->create();

    $this->actingAs($this->owner)->patch(route('staff.update', $foreign), ['role' => 'receptionist'])->assertNotFound();
    $this->actingAs($this->owner)->delete(route('staff.destroy', $foreign))->assertNotFound();

    expect($foreign->fresh()->role)->toBe(Role::Doctor);
});
