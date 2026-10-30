<?php

namespace App\Modules\Kompetensi\Actions;

use App\Modules\Fondasi\Enums\AuditAction;
use App\Modules\Fondasi\Models\User;
use App\Modules\Integrasi\Models\Employee;
use App\Modules\Kompetensi\Enums\AssessmentMethod;
use App\Modules\Kompetensi\Models\Assessment;
use App\Modules\Kompetensi\Models\JobStandard;
use App\Support\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * KOM-06: ASN mengisi asesmen mandiri untuk kompetensi SKJ jabatannya → menunggu validasi atasan.
 * Isian ulang untuk kompetensi yang masih menunggu menimpa isian lama (tidak menggandakan).
 */
class RecordSelfAssessment
{
    public function __construct(private readonly AuditLogger $audit) {}

    /**
     * @param  array<int, int|string|null>  $levels  competency_id => level 1–5 (kosong = dilewati)
     * @return list<Assessment>
     */
    public function __invoke(User $user, array $levels): array
    {
        $employee = $user->employee;
        if ($employee === null || ! $employee->isActive()) {
            throw ValidationException::withMessages(['levels' => __('kompetensi.self.no_employee')]);
        }
        if ($employee->supervisor_id === null) {
            throw ValidationException::withMessages(['levels' => __('kompetensi.self.no_supervisor')]);
        }

        $allowed = $this->skjCompetencyIds($employee);
        $levels = array_filter($levels, fn ($l) => $l !== null && $l !== '');
        if ($levels === []) {
            throw ValidationException::withMessages(['levels' => __('kompetensi.self.empty')]);
        }

        $errors = [];
        foreach ($levels as $competencyId => $level) {
            if (! in_array((int) $competencyId, $allowed, true)) {
                $errors["levels.{$competencyId}"] = __('kompetensi.self.not_in_skj');
            } elseif (! is_numeric($level) || (int) $level < 1 || (int) $level > 5) {
                $errors["levels.{$competencyId}"] = __('kompetensi.self.level_range');
            }
        }
        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        return DB::transaction(function () use ($employee, $levels, $user) {
            $saved = [];
            foreach ($levels as $competencyId => $level) {
                $assessment = Assessment::query()->pendingValidation()
                    ->where('employee_id', $employee->id)
                    ->where('competency_id', (int) $competencyId)
                    ->first() ?? new Assessment;
                $isNew = ! $assessment->exists;

                $assessment->fill([
                    'employee_id' => $employee->id,
                    'competency_id' => (int) $competencyId,
                    'level' => (int) $level,
                    'method' => AssessmentMethod::SelfAssessment,
                    'confidence' => AssessmentMethod::SelfAssessment->confidence(),
                    'assessed_on' => today(),
                ])->save();

                $this->audit->log($isNew ? AuditAction::Create : AuditAction::Update, $assessment,
                    changes: ['level' => (int) $level, 'method' => AssessmentMethod::SelfAssessment->value], userId: $user->id);
                $saved[] = $assessment;
            }

            return $saved;
        });
    }

    /** @return list<int> */
    public function skjCompetencyIds(Employee $employee): array
    {
        $standard = JobStandard::forPosition($employee->position);

        return $standard === null ? [] : $standard->items->pluck('competency_id')->map(fn ($id) => (int) $id)->values()->all();
    }
}
