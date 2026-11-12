<?php

namespace App\Http\Requests;

use App\Models\Patient;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Used for both creating and updating a patient.
 */
class PatientRequest extends FormRequest
{
    public function authorize(): bool
    {
        $patient = $this->route('patient');

        return $patient instanceof Patient
            ? $this->user()->can('update', $patient)
            : $this->user()->can('create', Patient::class);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $patient = $this->route('patient');

        return [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'phone' => [
                'required', 'string', 'max:32', 'regex:/^\+?[0-9\s\-()]{7,32}$/',
                // Phone numbers are unique per clinic, not globally.
                Rule::unique('patients', 'phone')
                    ->where('clinic_id', $this->user()->clinic_id)
                    ->ignore($patient),
            ],
            'email' => ['nullable', 'email', 'max:255'],
            'date_of_birth' => ['nullable', 'date', 'before_or_equal:today'],
            'gender' => ['nullable', Rule::in(['male', 'female'])],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }

    /**
     * Validated data, minus fields this user may not write.
     *
     * @return array<string, mixed>
     */
    public function patientData(): array
    {
        $data = $this->validated();

        if (! $this->user()->role->canAccessMedicalRecords()) {
            unset($data['notes']);
        }

        return $data;
    }
}
