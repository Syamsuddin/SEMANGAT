<?php

namespace App\Modules\JpRiwayat\Policies;

use App\Modules\Fondasi\Enums\Permission;
use App\Modules\Fondasi\Enums\Role;
use App\Modules\Fondasi\Models\User;
use App\Modules\JpRiwayat\Enums\JpStatus;
use App\Modules\JpRiwayat\Models\JpRecord;
use App\Support\Scope\DataScope;

/**
 * Aksi atas CatatanJP (docs/05 M12: asn S, atasan T, koord. O, pengelola K, clo/dewan A, auditor B).
 * Verifikasi sertifikat luar: pengelola (seluruh) atau koord. OPD (OPD-nya), tidak untuk catatan sendiri (docs/06 § 6).
 */
class JpRecordPolicy
{
    public function view(User $user, JpRecord $record): bool
    {
        return $user->is_active && JpRecord::query()->visibleTo($user)->whereKey($record->id)->exists();
    }

    public function verify(User $user, JpRecord $record): bool
    {
        if (! $user->is_active || ! $user->can(Permission::JpVerify->value) || $record->status !== JpStatus::Pending) {
            return false;
        }

        if ($user->employee_id !== null && $user->employee_id === $record->employee_id) {
            return false;
        }

        // Seluruh kabupaten hanya pengelola — auditor (B) baca-saja; koord. OPD hanya OPD penugasannya, juga bila merangkap auditor.
        return $user->hasRole(Role::Pengelola->value) || in_array($record->employee->opd_id, DataScope::for($user)->opdIds(), true);
    }
}
