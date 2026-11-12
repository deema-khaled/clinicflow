<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\Appointment;
use App\Models\User;

class AppointmentPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Appointment $appointment): bool
    {
        return $user->clinic_id === $appointment->clinic_id;
    }

    public function delete(User $user, Appointment $appointment): bool
    {
        return $user->clinic_id === $appointment->clinic_id
            && $user->hasRole(Role::Owner);
    }
}
