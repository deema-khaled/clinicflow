<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\Clinic;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Two demo clinics, so tenant isolation is easy to see in the browser.
     * Every account's password is "password".
     */
    public function run(): void
    {
        $demo = Clinic::factory()->create(['name' => 'Demo Dental Clinic']);

        User::factory()->forClinic($demo)->create([
            'name' => 'Demo Owner',
            'email' => 'owner@demo.test',
        ]);
        User::factory()->forClinic($demo)->role(Role::Doctor)->create([
            'name' => 'Dr. Demo',
            'email' => 'doctor@demo.test',
        ]);
        User::factory()->forClinic($demo)->role(Role::Receptionist)->create([
            'name' => 'Demo Reception',
            'email' => 'reception@demo.test',
        ]);
        Patient::factory(25)->forClinic($demo)->create();

        $other = Clinic::factory()->create(['name' => 'Other Family Clinic']);

        User::factory()->forClinic($other)->create([
            'name' => 'Other Owner',
            'email' => 'owner@other.test',
        ]);
        Patient::factory(10)->forClinic($other)->create();
    }
}
