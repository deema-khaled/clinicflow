<?php

namespace App\Http\Controllers;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Patient;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $today = CarbonImmutable::today();
        $weekStart = $today->startOfWeek(CarbonImmutable::SATURDAY);
        $monthStart = $today->startOfMonth();

        // Appointments per day for the current week (one query, grouped in PHP
        // so it works the same on PostgreSQL and SQLite).
        $weekCounts = Appointment::between($weekStart, $weekStart->addWeek())
            ->where('status', '!=', AppointmentStatus::Cancelled->value)
            ->pluck('starts_at')
            ->countBy(fn ($startsAt) => $startsAt->toDateString());

        $week = collect(range(0, 6))->map(function (int $i) use ($weekStart, $weekCounts) {
            $day = $weekStart->addDays($i);

            return [
                'label' => $day->format('D'),
                'date' => $day->toDateString(),
                'count' => $weekCounts->get($day->toDateString(), 0),
                'is_today' => $day->isToday(),
            ];
        });

        $todayAppointments = Appointment::with(['patient:id,first_name,last_name', 'doctor:id,name'])
            ->between($today, $today->addDay())
            ->orderBy('starts_at')
            ->get()
            ->map(fn (Appointment $a) => [
                'id' => $a->id,
                'start' => $a->starts_at->format('H:i'),
                'end' => $a->ends_at->format('H:i'),
                'status' => $a->status->value,
                'reason' => $a->reason,
                'patient' => $a->patient?->full_name,
                'doctor' => $a->doctor?->name,
            ]);

        $monthAppointments = Appointment::between($monthStart, $monthStart->addMonth())->get(['status']);
        $finished = $monthAppointments->whereIn('status', [AppointmentStatus::Completed, AppointmentStatus::NoShow])->count();

        return Inertia::render('Dashboard', [
            'stats' => [
                'patients' => Patient::count(),
                'new_patients_this_month' => Patient::where('created_at', '>=', $monthStart)->count(),
                'today' => $todayAppointments->where('status', '!=', AppointmentStatus::Cancelled->value)->count(),
                'today_remaining' => $todayAppointments->where('status', AppointmentStatus::Scheduled->value)->count(),
                'this_week' => $weekCounts->sum(),
                'completion_rate' => $finished > 0
                    ? (int) round($monthAppointments->where('status', AppointmentStatus::Completed)->count() / $finished * 100)
                    : null,
            ],
            'week' => $week,
            'todayAppointments' => $todayAppointments,
            'recentPatients' => Patient::latest()->limit(5)->get()->map(fn (Patient $p) => [
                'id' => $p->id,
                'name' => $p->full_name,
                'added' => $p->created_at->diffForHumans(),
            ]),
        ]);
    }
}
