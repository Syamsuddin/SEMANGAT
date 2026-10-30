<?php

namespace App\Modules\Kompetensi\Policies;

use App\Modules\Fondasi\Enums\Permission;
use App\Modules\Fondasi\Models\User;
use App\Modules\Kompetensi\Models\Assessment;

/**
 * Aksi atas HasilUji (docs/05 M03, docs/21 lapis 1). Cakupan baris tetap lewat Assessment::visibleTo.
 * Atasan hanya memvalidasi asesmen mandiri bawahan langsung dan tidak membuka laporan rinci.
 */
class AssessmentPolicy
{
    public function validate(User $user, Assessment $assessment): bool
    {
        return $user->is_active
            && $user->employee_id !== null
            && $assessment->isPending()
            && $assessment->employee->supervisor_id === $user->employee_id;
    }

    /** Laporan rinci: pemilik (S) atau petugas ber-izin (pengelola K*, auditor B*; wajib alasan — RecordSensitiveRead). */
    public function viewDetail(User $user, Assessment $assessment): bool
    {
        return $user->is_active
            && ($this->isOwner($user, $assessment) || $user->can(Permission::AssessmentViewDetail->value));
    }

    public function attachReport(User $user, Assessment $assessment): bool
    {
        return $user->is_active && ! $assessment->isSelfAssessment() && $user->can(Permission::CompetencyManage->value);
    }

    public function isOwner(User $user, Assessment $assessment): bool
    {
        return $user->employee_id !== null && $user->employee_id === $assessment->employee_id;
    }
}
