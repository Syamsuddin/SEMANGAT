<?php

namespace App\Modules\Idp\Policies;

use App\Modules\Fondasi\Models\User;
use App\Modules\Idp\Enums\IdpStatus;
use App\Modules\Idp\Models\Idp;

/**
 * Aksi atas IDP (docs/05 M06: asn K(S), atasan K(T), koord. O, pengelola/auditor B). Cakupan baris lewat Idp::visibleTo.
 * Persetujuan oleh atasan yang dibekukan saat ajukan; check-in & penutupan oleh atasan aktif (docs/06 § 4, mutasi).
 * asn tidak dapat menyetujui IDP sendiri.
 */
class IdpPolicy
{
    public function view(User $user, Idp $idp): bool
    {
        return $user->is_active && Idp::query()->visibleTo($user)->whereKey($idp->id)->exists();
    }

    public function update(User $user, Idp $idp): bool
    {
        return $this->isOwner($user, $idp) && $idp->status === IdpStatus::Draft;
    }

    public function approve(User $user, Idp $idp): bool
    {
        return $user->is_active
            && $user->employee_id !== null
            && ! $this->isOwner($user, $idp)
            && $idp->status === IdpStatus::Submitted
            && $idp->supervisor_id === $user->employee_id;
    }

    public function publish(User $user, Idp $idp): bool
    {
        return $this->isOwner($user, $idp) && $idp->status === IdpStatus::Approved;
    }

    public function checkin(User $user, Idp $idp): bool
    {
        return $this->isOwner($user, $idp) && $idp->status === IdpStatus::Active;
    }

    public function supervise(User $user, Idp $idp): bool
    {
        return $user->is_active
            && $user->employee_id !== null
            && ! $this->isOwner($user, $idp)
            && $idp->status === IdpStatus::Active
            && $idp->employee->supervisor_id === $user->employee_id;
    }

    public function isOwner(User $user, Idp $idp): bool
    {
        return $user->is_active && $user->employee_id !== null && $user->employee_id === $idp->employee_id;
    }
}
