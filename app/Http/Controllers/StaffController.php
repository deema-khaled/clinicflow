<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Clinic owners manage their team. Guarded by the "manage-clinic" gate.
 * Users are not tenant-scoped globally (auth needs to find them), so every
 * action here checks the clinic explicitly.
 */
class StaffController extends Controller
{
    public function index(Request $request): Response
    {
        $staff = User::where('clinic_id', $request->user()->clinic_id)
            ->withCount(['appointments as upcoming_appointments_count' => fn ($q) => $q
                ->where('starts_at', '>=', now())
                ->where('status', 'scheduled')])
            ->orderByRaw("case role when 'owner' then 0 when 'doctor' then 1 else 2 end")
            ->orderBy('name')
            ->get()
            ->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role->value,
                'is_me' => $user->is($request->user()),
                'upcoming_appointments' => $user->upcoming_appointments_count,
                'joined' => $user->created_at->format('j M Y'),
            ]);

        return Inertia::render('staff/Index', [
            'staff' => $staff,
            'roles' => collect(Role::cases())->map(fn (Role $r) => ['value' => $r->value, 'label' => $r->label()]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            'role' => ['required', Rule::in([Role::Doctor->value, Role::Receptionist->value])],
            'password' => ['required', Password::defaults()],
        ]);

        $member = $request->user()->clinic->users()->create([
            ...$validated,
            'password' => Hash::make($validated['password']),
        ]);
        // The owner created this account, so the email counts as verified.
        $member->forceFill(['email_verified_at' => now()])->save();

        return back()->with('success', "{$validated['name']} was added to your team.");
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $this->ensureSameClinic($request, $user);
        abort_if($user->is($request->user()), 403, 'You cannot change your own role.');

        $validated = $request->validate([
            'role' => ['required', Rule::enum(Role::class)],
        ]);

        $user->update($validated);

        return back()->with('success', "{$user->name} is now ".strtolower($user->role->label()).'.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        $this->ensureSameClinic($request, $user);
        abort_if($user->is($request->user()), 403, 'You cannot remove yourself.');

        $user->delete();

        return back()->with('success', "{$user->name} was removed from your team.");
    }

    private function ensureSameClinic(Request $request, User $user): void
    {
        // 404, not 403: don't reveal that the user exists in another clinic.
        abort_unless($user->clinic_id === $request->user()->clinic_id, 404);
    }
}
