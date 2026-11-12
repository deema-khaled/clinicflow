<?php

namespace App\Http\Controllers;

use App\Enums\AppointmentStatus;
use App\Enums\Role;
use App\Http\Requests\AppointmentRequest;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class AppointmentController extends Controller
{
    /** Weekly calendar. */
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Appointment::class);

        $weekStart = rescue(
            fn () => CarbonImmutable::parse((string) $request->query('week', 'today')),
            CarbonImmutable::today(),
            report: false,
        )->startOfWeek(CarbonImmutable::SATURDAY);
        $weekEnd = $weekStart->addWeek();
        $doctorId = $request->integer('doctor') ?: null;

        $appointments = Appointment::query()
            ->with(['patient:id,first_name,last_name,phone', 'doctor:id,name'])
            ->between($weekStart, $weekEnd)
            ->when($doctorId, fn ($q) => $q->where('doctor_id', $doctorId))
            ->orderBy('starts_at')
            ->get()
            ->map(fn (Appointment $a) => $this->present($a))
            ->groupBy('date');

        $days = collect(range(0, 6))->map(function (int $offset) use ($weekStart, $appointments) {
            $day = $weekStart->addDays($offset);

            return [
                'date' => $day->toDateString(),
                'weekday' => $day->format('D'),
                'day' => $day->format('j'),
                'month' => $day->format('M'),
                'is_today' => $day->isToday(),
                'appointments' => $appointments->get($day->toDateString(), collect())->values(),
            ];
        });

        return Inertia::render('appointments/Index', [
            'days' => $days,
            'week' => [
                'start' => $weekStart->toDateString(),
                'label' => $weekStart->format('j M').' – '.$weekEnd->subDay()->format('j M Y'),
                'previous' => $weekStart->subWeek()->toDateString(),
                'next' => $weekStart->addWeek()->toDateString(),
            ],
            'doctors' => $this->doctors(),
            'filters' => ['doctor' => $doctorId],
            'statuses' => AppointmentStatus::options(),
            'can' => ['delete' => $request->user()->hasRole(Role::Owner)],
        ]);
    }

    public function create(Request $request): Response
    {
        Gate::authorize('create', Appointment::class);

        return Inertia::render('appointments/Create', [
            ...$this->formOptions(),
            'defaults' => [
                'patient_id' => $request->integer('patient') ?: null,
                'date' => $request->query('date', today()->toDateString()),
            ],
        ]);
    }

    public function store(AppointmentRequest $request): RedirectResponse
    {
        $appointment = Appointment::create($request->appointmentData());

        return to_route('appointments.index', ['week' => $appointment->starts_at->toDateString()])
            ->with('success', 'Appointment booked for '.$appointment->starts_at->format('D j M, H:i').'.');
    }

    public function edit(Appointment $appointment): Response
    {
        Gate::authorize('update', $appointment);

        return Inertia::render('appointments/Edit', [
            ...$this->formOptions(),
            'appointment' => [
                'id' => $appointment->id,
                'patient_id' => $appointment->patient_id,
                'doctor_id' => $appointment->doctor_id,
                'date' => $appointment->starts_at->toDateString(),
                'start_time' => $appointment->starts_at->format('H:i'),
                'duration' => (int) $appointment->starts_at->diffInMinutes($appointment->ends_at),
                'status' => $appointment->status->value,
                'reason' => $appointment->reason,
                'notes' => $appointment->notes,
            ],
            'canDelete' => request()->user()->can('delete', $appointment),
        ]);
    }

    public function update(AppointmentRequest $request, Appointment $appointment): RedirectResponse
    {
        $appointment->update($request->appointmentData());

        return to_route('appointments.index', ['week' => $appointment->starts_at->toDateString()])
            ->with('success', 'Appointment updated.');
    }

    /** Quick status change from the calendar (complete, cancel, no-show). */
    public function updateStatus(Request $request, Appointment $appointment): RedirectResponse
    {
        Gate::authorize('update', $appointment);

        $validated = $request->validate([
            'status' => ['required', Rule::enum(AppointmentStatus::class)],
        ]);

        $status = AppointmentStatus::from($validated['status']);

        // Re-activating a cancelled slot must not create a double-booking.
        if ($status->blocksTimeSlot() && ! $appointment->status->blocksTimeSlot()) {
            $clash = Appointment::overlapping($appointment->doctor_id, $appointment->starts_at, $appointment->ends_at, $appointment->id)->exists();
            if ($clash) {
                return back()->withErrors(['status' => 'That time slot has been booked since this appointment was cancelled.']);
            }
        }

        $appointment->update(['status' => $status]);

        return back()->with('success', 'Marked as '.strtolower($status->label()).'.');
    }

    public function destroy(Appointment $appointment): RedirectResponse
    {
        Gate::authorize('delete', $appointment);

        $week = $appointment->starts_at->toDateString();
        $appointment->delete();

        return to_route('appointments.index', ['week' => $week])->with('success', 'Appointment deleted.');
    }

    /** @return array<string, mixed> */
    private function present(Appointment $a): array
    {
        return [
            'id' => $a->id,
            'date' => $a->starts_at->toDateString(),
            'start' => $a->starts_at->format('H:i'),
            'end' => $a->ends_at->format('H:i'),
            'status' => $a->status->value,
            'reason' => $a->reason,
            'patient' => ['id' => $a->patient_id, 'name' => $a->patient?->full_name, 'phone' => $a->patient?->phone],
            'doctor' => ['id' => $a->doctor_id, 'name' => $a->doctor?->name],
        ];
    }

    /** @return list<array{id: int, name: string}> */
    private function doctors(): array
    {
        return User::where('clinic_id', request()->user()->clinic_id)
            ->bookable()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->toArray();
    }

    /** @return array<string, mixed> */
    private function formOptions(): array
    {
        return [
            'patients' => Patient::orderBy('first_name')->orderBy('last_name')
                ->get(['id', 'first_name', 'last_name', 'phone'])
                ->map(fn (Patient $p) => ['id' => $p->id, 'name' => $p->full_name, 'phone' => $p->phone]),
            'doctors' => $this->doctors(),
            'durations' => AppointmentRequest::DURATIONS,
            'statuses' => AppointmentStatus::options(),
        ];
    }
}
