<?php

namespace App\Models;

use App\Enums\AppointmentStatus;
use App\Models\Concerns\BelongsToClinic;
use Carbon\CarbonInterface;
use Database\Factories\AppointmentFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Appointment extends Model
{
    /** @use HasFactory<AppointmentFactory> */
    use BelongsToClinic, HasFactory;

    protected $fillable = [
        'patient_id',
        'doctor_id',
        'starts_at',
        'ends_at',
        'status',
        'reason',
        'notes',
    ];

    protected $attributes = [
        'status' => 'scheduled',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'status' => AppointmentStatus::class,
        ];
    }

    /** @return BelongsTo<Patient, $this> */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class)->withTrashed();
    }

    /** @return BelongsTo<User, $this> */
    public function doctor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }

    /**
     * Appointments of a doctor that overlap [$start, $end).
     * Two ranges overlap when each one starts before the other ends.
     *
     * @param  Builder<Appointment>  $query
     */
    public function scopeOverlapping(Builder $query, int $doctorId, CarbonInterface $start, CarbonInterface $end, ?int $ignoreId = null): void
    {
        $query->where('doctor_id', $doctorId)
            ->where('status', '!=', AppointmentStatus::Cancelled->value)
            ->where('starts_at', '<', $end)
            ->where('ends_at', '>', $start)
            ->when($ignoreId, fn (Builder $q) => $q->whereKeyNot($ignoreId));
    }

    /** @param  Builder<Appointment>  $query */
    public function scopeBetween(Builder $query, CarbonInterface $from, CarbonInterface $to): void
    {
        $query->where('starts_at', '>=', $from)->where('starts_at', '<', $to);
    }
}
