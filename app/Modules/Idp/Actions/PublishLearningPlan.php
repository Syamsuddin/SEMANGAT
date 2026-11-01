<?php

namespace App\Modules\Idp\Actions;

use App\Modules\Fondasi\Enums\AuditAction;
use App\Modules\Fondasi\Models\User;
use App\Modules\Idp\Documents\LearningPlanContent;
use App\Modules\Idp\Enums\IdpStatus;
use App\Modules\Idp\Models\Idp;
use App\Support\AuditLogger;
use App\Support\DocumentIssuer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

/**
 * IDP-05: terbitkan Rencana Pembelajaran dari IDP `approved` → `active`. PDF ber-QR dibuat di queue `documents`
 * (keputusan PDF 2026-11-01) dan tercatat di out_issued_documents; pegawai dikabari saat siap (FON-07).
 */
class PublishLearningPlan
{
    public function __construct(
        private readonly DocumentIssuer $issuer,
        private readonly AuditLogger $audit,
    ) {}

    public function handle(User $user, Idp $idp): Idp
    {
        Gate::forUser($user)->authorize('publish', $idp);

        return DB::transaction(function () use ($user, $idp) {
            $idp->forceFill(['status' => IdpStatus::Active])->save();
            $this->audit->log(AuditAction::Export, $idp, changes: ['status' => IdpStatus::Active->value, 'document' => 'learning_plan'], userId: $user->id);
            $this->issuer->queue(LearningPlanContent::class, $idp, $idp->employee_id, $user);

            return $idp;
        });
    }
}
