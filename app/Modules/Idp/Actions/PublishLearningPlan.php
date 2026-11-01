<?php

namespace App\Modules\Idp\Actions;

use App\Modules\Fondasi\Enums\AuditAction;
use App\Modules\Fondasi\Enums\DocumentType;
use App\Modules\Fondasi\Models\IssuedDocument;
use App\Modules\Fondasi\Models\User;
use App\Modules\Idp\Enums\IdpStatus;
use App\Modules\Idp\Models\Idp;
use App\Modules\Idp\Services\CompetentExpectation;
use App\Support\AuditLogger;
use App\Support\DocumentIssuer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

/**
 * IDP-05: terbitkan Rencana Pembelajaran (PDF ber-QR, tercatat di out_issued_documents) dari IDP `approved` → `active`.
 * Isi: kompetensi prioritas, tautan RHK, aktivitas 10:20:70, target JP, teks ekspektasi "Kompeten" per jenjang.
 */
class PublishLearningPlan
{
    public function __construct(
        private readonly DocumentIssuer $issuer,
        private readonly CompetentExpectation $expectation,
        private readonly AuditLogger $audit,
    ) {}

    public function handle(User $user, Idp $idp): IssuedDocument
    {
        Gate::forUser($user)->authorize('publish', $idp);

        $idp->load(['employee.position', 'employee.opd', 'supervisor', 'items.competency', 'items.workResult', 'items.mentor', 'items.activities']);

        return DB::transaction(function () use ($user, $idp) {
            $document = $this->issuer->issue(DocumentType::LearningPlan, $idp, $idp->employee_id, 'idp::pdf.learning-plan', [
                'idp' => $idp,
                'expectation' => $this->expectation->for($idp->employee->position?->tier),
            ]);

            $idp->forceFill(['status' => IdpStatus::Active])->save();
            $this->audit->log(AuditAction::Export, $idp, changes: ['status' => IdpStatus::Active->value, 'document' => $document->uuid], userId: $user->id);

            return $document;
        });
    }
}
