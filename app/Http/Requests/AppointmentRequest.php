<?php

namespace App\Http\Requests;

use App\Enums\AppointmentStatus;
use App\Enums\Role;
use App\Models\Appointment;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class AppointmentRequest extends FormRequest
{
    public const DURATIONS = [15, 20, 30, 45, 60, 90, 120];

    public function authorize(): bool
    {
        $appointment = $this->route('appointment');

        return $appointment instanceof Appointment
            ? $this->user()->can('update', $appointment)
            : $this->user()->can('create', Appointment::class);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $clinicId = $this->user()->clinic_id;

        return [
            // Both must belong to the same clinic as the signed-in user.
            'patient_id' => ['required', 'integer', Rule::exists('patients', 'id')
                ->where('clinic_id', $clinicId)
                ->whereNull('deleted_at')],
            'doctor_id' => ['required', 'integer', Rule::exists('users', 'id')
                ->where('clinic_id', $clinicId)
                ->whereIn('role', [Role::Doctor->value, Role::Owner->value])],
            'date' => ['required', 'date_format:Y-m-d'],
            'start_time' => ['required', 'date_format:H:i'],
            'duration' => ['required', 'integer', Rule::in(self::DURATIONS)],
            'status' => ['sometimes', Rule::enum(AppointmentStatus::class)],
            'reason' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }

    /** No double-booking: the doctor must be free for the whole slot. */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $status = AppointmentStatus::tryFrom((string) $this->input('status', 'scheduled'));
                if ($status && ! $status->blocksTimeSlot()) {
                    return;
                }

                [$start, $end] = $this->period();
                $current = $this->route('appointment');

                $clash = Appointment::overlapping(
                    (int) $this->input('doctor_id'), $start, $end,
                    $current instanceof Appointment ? $current->id : null,
                )->with('patient')->first();

                if ($clash) {
                    $validator->errors()->add('start_time', sprintf(
                        'This doctor is already booked %s–%s (%s).',
                        $clash->starts_at->format('H:i'),
                        $clash->ends_at->format('H:i'),
                        $clash->patient?->full_name ?? 'another patient',
                    ));
                }
            },
        ];
    }

    /** @return array{0: CarbonImmutable, 1: CarbonImmutable} */
    public function period(): array
    {
        $start = CarbonImmutable::createFromFormat('Y-m-d H:i', $this->input('date').' '.$this->input('start_time'))->startOfMinute();

        return [$start, $start->addMinutes((int) $this->input('duration'))];
    }

    /** @return array<string, mixed> */
    public function appointmentData(): array
    {
        [$start, $end] = $this->period();
        $data = $this->safe()->only(['patient_id', 'doctor_id', 'status', 'reason', 'notes']);

        return [...$data, 'starts_at' => $start, 'ends_at' => $end];
    }
}
