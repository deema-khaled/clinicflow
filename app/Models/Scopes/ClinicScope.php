<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

class ClinicScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        // hasUser() only checks the already-resolved user, so this never
        // triggers an extra auth lookup or recursion.
        if (Auth::hasUser()) {
            $builder->where($model->qualifyColumn('clinic_id'), Auth::user()->clinic_id);
        }
    }
}
