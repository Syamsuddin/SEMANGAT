<?php

namespace App\Modules\Kompetensi\Actions;

use App\Modules\Fondasi\Actions\RecordSensitiveRead;
use App\Modules\Fondasi\Enums\AuditAction;
use App\Modules\Fondasi\Models\AuditLog;
use App\Modules\Fondasi\Models\User;
use App\Modules\Kompetensi\Models\Assessment;
use App\Modules\Kompetensi\Policies\AssessmentPolicy;
use App\Modules\Kompetensi\Services\SensitiveFileStore;
use App\Support\AuditLogger;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * FON-05 di F2: membuka laporan asesmen rinci. Pemilik (S) → tercatat tanpa alasan;
 * petugas ber-izin (pengelola K*, auditor B*) → wajib alasan via RecordSensitiveRead. Setiap buka & unduh = baris audit.
 */
class OpenAssessmentReport
{
    public function __construct(
        private readonly RecordSensitiveRead $recordSensitiveRead,
        private readonly AssessmentPolicy $policy,
        private readonly SensitiveFileStore $files,
        private readonly AuditLogger $audit,
    ) {}

    public function open(User $user, Assessment $assessment, ?string $reason = null): AuditLog
    {
        Gate::forUser($user)->authorize('viewDetail', $assessment);

        if ($this->policy->isOwner($user, $assessment)) {
            return $this->audit->log(AuditAction::ReadSensitive, $assessment, userId: $user->id);
        }

        return ($this->recordSensitiveRead)($user, $assessment, $reason);
    }

    /** Unduh berkas — hanya setelah open() berhasil di sesi halaman yang sama (dijaga pemanggil). */
    public function download(User $user, Assessment $assessment): StreamedResponse
    {
        Gate::forUser($user)->authorize('viewDetail', $assessment);
        abort_if($assessment->report_file_path === null, 404);

        $path = $assessment->report_file_path;
        $contents = $this->files->get($path);
        $this->audit->log(AuditAction::Export, $assessment, changes: ['report_file' => 'downloaded'], userId: $user->id);

        $filename = 'laporan-asesmen-'.$assessment->id.'-'.Str::slug($assessment->competency->code).'.'.$this->files->extension($path);

        return response()->streamDownload(fn () => print ($contents), $filename);
    }

    /**
     * Transparansi PDP (docs/05, docs/21): siapa membuka laporan asesmen milik pegawai ini.
     *
     * @return Collection<int, AuditLog>
     */
    public function accessLog(int $employeeId): Collection
    {
        $assessmentIds = Assessment::query()->where('employee_id', $employeeId)->pluck('id');

        return AuditLog::query()
            ->with('user.employee:id,name')
            ->where('auditable_type', (new Assessment)->getMorphClass())
            ->whereIn('auditable_id', $assessmentIds)
            ->whereIn('action', [AuditAction::ReadSensitive->value, AuditAction::Export->value])
            ->latest('created_at')
            ->limit(50)
            ->get();
    }
}
