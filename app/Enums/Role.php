<?php

namespace App\Enums;

enum Role: string
{
    case Owner = 'owner';
    case Doctor = 'doctor';
    case Receptionist = 'receptionist';

    public function label(): string
    {
        return match ($this) {
            self::Owner => 'Owner',
            self::Doctor => 'Doctor',
            self::Receptionist => 'Receptionist',
        };
    }

    /** Owners manage the clinic itself: settings, staff, billing. */
    public function canManageClinic(): bool
    {
        return $this === self::Owner;
    }

    /** Doctors and owners can read and write medical notes. */
    public function canAccessMedicalRecords(): bool
    {
        return $this !== self::Receptionist;
    }
}
