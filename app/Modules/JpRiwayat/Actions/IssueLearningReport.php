<?php

namespace App\Modules\JpRiwayat\Actions;

use App\Modules\Fondasi\Enums\AuditAction;
use App\Modules\Fondasi\Models\User;
use App\Modules\Idp\Enums\IdpStatus;
use App\Modules\Idp\Models\Idp;
use App\Modules\JpRiwayat\Documents\LearningReportContent;
use App\Support\AuditLogger;
use App\Support\DocumentIssuer;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;

/**
 * JPR-05: pegawai menerbitkan Laporan Hasil Pembelajaran atas IDP `closed` miliknya (PDF ber-QR, queue `documents`).
 * Laporan diunggah pegawai sendiri ke e-Kinerja sebagai bukti dukung "Kompeten" — Corpu tidak menulis ke e-Kinerja.
 */
class IssueLearningReport
{
    public function __construct(private readonly DocumentIssuer $issuer, private readonly AuditLogger $audit) {}

    public function handle(User $user, Idp $idp): void
    {
        if (! $user->is_active || $user->employee_id === null || $user->employee_id !== $idp->employee_id || $idp->status !== IdpStatus::Closed) {
            throw new AuthorizationException;
        }

        if ($idp->learningReport()->exists()) {
            throw ValidationException::withMessages(['report' => __('jp.errors.report_exists')]);
        }

        $this->audit->log(AuditAction::Export, $idp, changes: ['document' => 'learning_report'], userId: $user->id);
        $this->issuer->queue(LearningReportContent::class, $idp, $idp->employee_id, $user);
    }
}
