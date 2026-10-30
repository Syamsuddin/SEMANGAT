<?php

namespace App\Modules\Kompetensi\Actions;

use App\Modules\Fondasi\Enums\AuditAction;
use App\Modules\Fondasi\Models\User;
use App\Modules\Kompetensi\Events\AssessmentRecorded;
use App\Modules\Kompetensi\Models\Assessment;
use App\Support\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

/**
 * KOM-06: atasan langsung menyetujui (→ dipakai mesin kesenjangan sebagai `provisional`, dihitung ulang via queue)
 * atau menolak dengan alasan (→ tidak dipakai). Policy: AssessmentPolicy@validate.
 */
class ValidateSelfAssessment
{
    public const MIN_NOTE_LENGTH = 5;

    public function __construct(private readonly AuditLogger $audit) {}

    public function approve(User $user, Assessment $assessment): Assessment
    {
        Gate::forUser($user)->authorize('validate', $assessment);

        DB::transaction(function () use ($user, $assessment) {
            $assessment->forceFill(['validated_by' => $user->employee_id, 'validated_at' => now()])->save();
            $this->audit->log(AuditAction::Update, $assessment, changes: ['validation' => 'approved'], userId: $user->id);
        });

        AssessmentRecorded::dispatch($assessment->employee_id);

        return $assessment;
    }

    public function reject(User $user, Assessment $assessment, ?string $note): Assessment
    {
        Gate::forUser($user)->authorize('validate', $assessment);

        $note = trim((string) $note);
        if (mb_strlen($note) < self::MIN_NOTE_LENGTH) {
            throw ValidationException::withMessages(['note' => __('kompetensi.validation.note_required', ['min' => self::MIN_NOTE_LENGTH])]);
        }

        DB::transaction(function () use ($user, $assessment, $note) {
            $assessment->forceFill([
                'validated_by' => $user->employee_id,
                'rejected_at' => now(),
                'rejection_note' => mb_substr($note, 0, 255),
            ])->save();
            $this->audit->log(AuditAction::Update, $assessment, changes: ['validation' => 'rejected'], userId: $user->id);
        });

        return $assessment;
    }
}
