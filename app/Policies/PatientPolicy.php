<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\Patient;
use App\Models\User;

/**
 * Tenant isolation is already enforced by ClinicScope; the clinic check here
 * is a second line of defence in case a patient is ever loaded unscoped.
 */
class PatientPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Patient $patient): bool
    {
        return $user->clinic_id === $patient->clinic_id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Patient $patient): bool
    {
        return $user->clinic_id === $patient->clinic_id;
    }

    public function delete(User $user, Patient $patient): bool
    {
        return $user->clinic_id === $patient->clinic_id
            && $user->hasRole(Role::Owner);
    }

    /** Medical notes are hidden from receptionists. */
    public function viewMedicalNotes(User $user, Patient $patient): bool
    {
        return $user->clinic_id === $patient->clinic_id
            && $user->role->canAccessMedicalRecords();
    }
}
