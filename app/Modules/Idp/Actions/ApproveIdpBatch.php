<?php

namespace App\Modules\Idp\Actions;

use App\Modules\Fondasi\Enums\AuditAction;
use App\Modules\Fondasi\Models\User;
use App\Modules\Idp\Enums\IdpStatus;
use App\Modules\Idp\Models\Idp;
use App\Modules\Idp\Notifications\IdpApproved;
use App\Support\AuditLogger;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

/**
 * IDP-04: atasan menyetujui satu atau banyak IDP `submitted` sekaligus dengan satu catatan.
 * Semua-atau-tidak: satu IDP di luar wewenang → seluruh batch ditolak. Tiap pegawai menerima notifikasi.
 */
class ApproveIdpBatch
{
    public function __construct(private readonly AuditLogger $audit) {}

    /**
     * @param  list<int>  $idpIds
     * @return Collection<int, Idp>
     */
    public function handle(User $user, array $idpIds, ?string $note = null): Collection
    {
        $idpIds = array_values(array_unique(array_map('intval', $idpIds)));
        if ($idpIds === []) {
            throw ValidationException::withMessages(['selected' => __('idp.errors.select_one')]);
        }

        $idps = Idp::query()->visibleTo($user)->whereKey($idpIds)->with('employee.user')->get();
        abort_if($idps->count() !== count($idpIds), 404);
        $idps->each(fn (Idp $idp) => Gate::forUser($user)->authorize('approve', $idp));

        $note = trim((string) $note) ?: null;

        DB::transaction(function () use ($user, $idps, $note) {
            foreach ($idps as $idp) {
                $idp->forceFill(['status' => IdpStatus::Approved, 'approved_at' => now(), 'supervisor_note' => $note])->save();
                $this->audit->log(AuditAction::Update, $idp, changes: ['status' => IdpStatus::Approved->value], userId: $user->id);
            }
        });

        $idps->each(fn (Idp $idp) => $idp->employee->user?->notify(new IdpApproved($idp, $note)));

        return $idps;
    }
}
