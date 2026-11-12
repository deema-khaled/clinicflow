<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Http\Requests\PatientRequest;
use App\Models\Patient;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class PatientController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Patient::class);

        $search = trim((string) $request->query('search', ''));

        $patients = Patient::query()
            ->when($search !== '', function (Builder $query) use ($search) {
                $query->where(function (Builder $q) use ($search) {
                    foreach (['first_name', 'last_name', 'phone', 'email'] as $column) {
                        $q->orWhereLike($column, "%{$search}%");
                    }
                });
            })
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->paginate(10)
            ->withQueryString()
            ->through(fn (Patient $patient) => [
                'id' => $patient->id,
                'full_name' => $patient->full_name,
                'phone' => $patient->phone,
                'email' => $patient->email,
                'age' => $patient->date_of_birth?->age,
                'gender' => $patient->gender,
            ]);

        return Inertia::render('patients/Index', [
            'patients' => $patients,
            'filters' => ['search' => $search],
            // UI hint only; PatientPolicy::delete() is still enforced on the request.
            'can' => ['delete' => $request->user()->hasRole(Role::Owner)],
        ]);
    }

    public function create(Request $request): Response
    {
        Gate::authorize('create', Patient::class);

        return Inertia::render('patients/Create', [
            'canEditNotes' => $request->user()->role->canAccessMedicalRecords(),
        ]);
    }

    public function store(PatientRequest $request): RedirectResponse
    {
        $patient = Patient::create($request->patientData());

        return to_route('patients.index')
            ->with('success', "{$patient->full_name} was added.");
    }

    public function edit(Request $request, Patient $patient): Response
    {
        Gate::authorize('update', $patient);

        $canEditNotes = $request->user()->can('viewMedicalNotes', $patient);

        return Inertia::render('patients/Edit', [
            'patient' => [
                'id' => $patient->id,
                'first_name' => $patient->first_name,
                'last_name' => $patient->last_name,
                'phone' => $patient->phone,
                'email' => $patient->email,
                'date_of_birth' => $patient->date_of_birth?->toDateString(),
                'gender' => $patient->gender,
                // Never sent to the browser for users who may not read it.
                'notes' => $canEditNotes ? $patient->notes : null,
            ],
            'canEditNotes' => $canEditNotes,
            'canDelete' => $request->user()->can('delete', $patient),
        ]);
    }

    public function update(PatientRequest $request, Patient $patient): RedirectResponse
    {
        $patient->update($request->patientData());

        return to_route('patients.index')
            ->with('success', "{$patient->full_name} was updated.");
    }

    public function destroy(Patient $patient): RedirectResponse
    {
        Gate::authorize('delete', $patient);

        $patient->delete();

        return to_route('patients.index')
            ->with('success', "{$patient->full_name} was removed.");
    }
}
