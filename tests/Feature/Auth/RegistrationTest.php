<?php

use App\Enums\Role;
use App\Models\Clinic;
use App\Models\User;

test('registration screen can be rendered', function () {
    $response = $this->get('/register');

    $response->assertStatus(200);
});

test('registering creates a clinic with the user as its owner', function () {
    $response = $this->post('/register', [
        'clinic_name' => 'Al Noor Dental',
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));

    $user = User::firstWhere('email', 'test@example.com');
    $clinic = Clinic::sole();

    expect($clinic->name)->toBe('Al Noor Dental')
        ->and($clinic->slug)->toBe('al-noor-dental')
        ->and($user->clinic_id)->toBe($clinic->id)
        ->and($user->role)->toBe(Role::Owner);
});

test('clinic name is required', function () {
    $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertSessionHasErrors('clinic_name');

    $this->assertGuest();
    expect(Clinic::count())->toBe(0);
});

test('clinics with the same name get unique slugs', function () {
    $first = Clinic::create(['name' => 'City Clinic']);
    $second = Clinic::create(['name' => 'City Clinic']);

    expect($first->slug)->toBe('city-clinic')
        ->and($second->slug)->toStartWith('city-clinic-')
        ->and($second->slug)->not->toBe($first->slug);
});
