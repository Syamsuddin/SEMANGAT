<?php

namespace App\Modules\Fondasi\Actions;

use App\Modules\Fondasi\Enums\AuditAction;
use App\Modules\Fondasi\Enums\Permission;
use App\Modules\Fondasi\Models\AuditLog;
use App\Modules\Fondasi\Models\User;
use App\Support\AuditLogger;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

/**
 * FON-05: gerbang pembukaan data rinci Sensitivitas Tinggi — izin + alasan wajib → 1 baris read_sensitive.
 * Dipakai halaman laporan asesmen rinci (F2).
 */
class RecordSensitiveRead
{
    public const MIN_REASON_LENGTH = 10;

    public function __construct(private readonly AuditLogger $audit) {}

    public function __invoke(User $user, Model $record, ?string $reason, Permission $permission = Permission::AssessmentViewDetail): AuditLog
    {
        if (! $user->can($permission->value)) {
            throw new AuthorizationException(__('fondasi.audit.not_permitted'));
        }

        $reason = trim((string) $reason);
        if (mb_strlen($reason) < self::MIN_REASON_LENGTH) {
            throw ValidationException::withMessages([
                'reason' => __('fondasi.audit.reason_required', ['min' => self::MIN_REASON_LENGTH]),
            ]);
        }

        return $this->audit->log(AuditAction::ReadSensitive, $record, mb_substr($reason, 0, 255), userId: $user->id);
    }
}
