<?php

namespace App\Modules\Idp\Actions;

use App\Modules\Fondasi\Enums\AuditAction;
use App\Modules\Fondasi\Models\User;
use App\Modules\Idp\Enums\IdpStatus;
use App\Modules\Idp\Models\Idp;
use App\Modules\Idp\Notifications\IdpSubmitted;
use App\Support\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

/**
 * Ajukan IDP (docs/06 § 4 langkah 3): 2–3 butir (IDP-02); butir tanpa RHK tetap boleh, IDP ditandai (IDP-10);
 * atasan langsung dibekukan sebagai penyetuju lalu menerima notifikasi.
 */
class SubmitIdp
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function handle(User $user, Idp $idp): Idp
    {
        Gate::forUser($user)->authorize('update', $idp);

        $count = $idp->items()->count();
        if ($count < Idp::MIN_ITEMS) {
            throw ValidationException::withMessages(['items' => __('idp.errors.min_items', ['min' => Idp::MIN_ITEMS])]);
        }
        if ($count > Idp::MAX_ITEMS) {
            throw ValidationException::withMessages(['items' => __('idp.errors.max_items', ['max' => Idp::MAX_ITEMS])]);
        }

        $supervisorId = $idp->employee->supervisor_id;
        if ($supervisorId === null) {
            throw ValidationException::withMessages(['items' => __('idp.errors.no_supervisor')]);
        }

        DB::transaction(function () use ($user, $idp, $supervisorId) {
            $unlinked = $idp->items()->whereNull('skp_work_result_id')->count();
            $idp->forceFill([
                'status' => IdpStatus::Submitted,
                'supervisor_id' => $supervisorId,
                'submitted_at' => now(),
                'unlinked_items_count' => $unlinked,
            ])->save();
            $this->audit->log(AuditAction::Update, $idp, changes: ['status' => IdpStatus::Submitted->value, 'unlinked_items_count' => $unlinked], userId: $user->id);
        });

        $idp->load('supervisor.user', 'employee');
        $idp->supervisor?->user?->notify(new IdpSubmitted($idp));

        return $idp;
    }
}
