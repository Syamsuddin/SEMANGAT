<?php

namespace App\Modules\Idp\Actions;

use App\Modules\Fondasi\Enums\AuditAction;
use App\Modules\Fondasi\Models\User;
use App\Modules\Idp\Enums\IdpStatus;
use App\Modules\Idp\Models\Idp;
use App\Support\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * IDP-07: penutupan akhir periode — pegawai menulis refleksi, atasan aktif menilai penerapan di pekerjaan → `closed`.
 * Bukti & Laporan Hasil Pembelajaran dirakit di M12 (F4).
 */
class CloseIdp
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function reflect(User $user, Idp $idp, ?string $reflection): Idp
    {
        Gate::forUser($user)->authorize('checkin', $idp);

        $data = Validator::make(['closing_reflection' => $reflection], ['closing_reflection' => ['required', 'string', 'min:5', 'max:5000']], [], __('idp.attributes'))->validate();
        $idp->forceFill($data)->save();

        return $idp;
    }

    public function close(User $user, Idp $idp, ?string $assessment): Idp
    {
        Gate::forUser($user)->authorize('supervise', $idp);

        if (blank($idp->closing_reflection)) {
            throw ValidationException::withMessages(['closing_assessment' => __('idp.errors.reflection_missing')]);
        }

        $data = Validator::make(['closing_assessment' => $assessment], ['closing_assessment' => ['required', 'string', 'min:5', 'max:5000']], [], __('idp.attributes'))->validate();

        DB::transaction(function () use ($user, $idp, $data) {
            $idp->forceFill($data + ['status' => IdpStatus::Closed, 'closed_at' => now()])->save();
            $this->audit->log(AuditAction::Update, $idp, changes: ['status' => IdpStatus::Closed->value], userId: $user->id);
        });

        return $idp;
    }
}
