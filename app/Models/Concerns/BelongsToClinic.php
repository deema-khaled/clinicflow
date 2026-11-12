<?php

namespace App\Models\Concerns;

use App\Models\Clinic;
use App\Models\Scopes\ClinicScope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

/**
 * Makes a model tenant-aware (single database, shared schema):
 *
 *  - every query is limited to the signed-in user's clinic (ClinicScope)
 *  - new records get the signed-in user's clinic_id automatically
 *
 * Code without a signed-in user (console commands, queued jobs) is NOT
 * scoped, so it must filter by clinic itself, e.g. `$clinic->patients()`.
 */
trait BelongsToClinic
{
    public static function bootBelongsToClinic(): void
    {
        static::addGlobalScope(new ClinicScope);

        // clinic_id is deliberately NOT fillable. For a signed-in user it is
        // always forced to their own clinic, so request data can never plant
        // a record in another tenant.
        static::creating(function ($model) {
            if (Auth::hasUser()) {
                $model->clinic_id = Auth::user()->clinic_id;
            }
        });
    }

    /** @return BelongsTo<Clinic, $this> */
    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }
}
