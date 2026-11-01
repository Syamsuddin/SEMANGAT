<?php

namespace App\Modules\Kompetensi\Actions;

use App\Modules\Fondasi\Enums\AuditAction;
use App\Modules\Fondasi\Models\User;
use App\Modules\Kompetensi\Models\Assessment;
use App\Modules\Kompetensi\Services\SensitiveFileStore;
use App\Support\AuditLogger;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

/**
 * Pengelola melampirkan berkas laporan asesmen rinci/psikogram (🔒, keputusan pemilik produk 2026-10-30).
 * Berkas dienkripsi sebelum disimpan; berkas lama diganti. Audit tanpa nilai 🔒.
 */
class AttachAssessmentReport
{
    public function __construct(private readonly SensitiveFileStore $files, private readonly AuditLogger $audit) {}

    public function __invoke(User $user, Assessment $assessment, UploadedFile $file): Assessment
    {
        Gate::forUser($user)->authorize('attachReport', $assessment);

        $extension = strtolower($file->getClientOriginalExtension()) ?: (string) $file->extension();
        $newPath = $this->files->put((string) $file->get(), $extension);
        $oldPath = $assessment->report_file_path;

        DB::transaction(function () use ($assessment, $newPath, $oldPath, $user) {
            $assessment->forceFill(['report_file_path' => $newPath])->save();
            $this->audit->log(AuditAction::Update, $assessment, changes: ['report_file' => $oldPath === null ? 'attached' : 'replaced'], userId: $user->id);
        });

        if ($oldPath !== null) {
            $this->files->delete($oldPath);
        }

        return $assessment;
    }
}
