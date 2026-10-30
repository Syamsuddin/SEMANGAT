<?php

namespace App\Modules\Kompetensi\Actions;

use App\Modules\Integrasi\Models\Employee;
use App\Modules\Kompetensi\Data\GapLine;
use App\Modules\Kompetensi\Models\Assessment;
use App\Modules\Kompetensi\Models\Gap;
use App\Modules\Kompetensi\Models\JobStandard;
use App\Modules\Kompetensi\Services\GapCalculator;
use Illuminate\Support\Facades\DB;

/**
 * Materialisasi cmp_gaps untuk satu pegawai (KOM-04, keputusan A4). Hanya gap > 0 disimpan (docs/06 § 3).
 * Pegawai nonaktif atau tanpa SKJ → kesenjangannya dikosongkan.
 */
class RecalculateGaps
{
    public function __construct(private readonly GapCalculator $calculator) {}

    /** @return list<GapLine> seluruh butir SKJ (termasuk yang memenuhi) */
    public function __invoke(Employee $employee): array
    {
        $lines = $employee->isActive() ? $this->linesFor($employee) : [];
        $gaps = array_values(array_filter($lines, fn (GapLine $l) => $l->hasGap()));

        DB::transaction(function () use ($employee, $gaps) {
            Gap::query()->where('employee_id', $employee->id)
                ->whereNotIn('competency_id', array_map(fn (GapLine $l) => $l->competencyId, $gaps))
                ->delete();

            $now = now();
            foreach ($gaps as $line) {
                Gap::query()->updateOrCreate(
                    ['employee_id' => $employee->id, 'competency_id' => $line->competencyId],
                    [
                        'required_level' => $line->requiredLevel,
                        'actual_level' => $line->actualLevel,
                        'gap' => $line->gap,
                        'confidence' => $line->confidence,
                        'assessment_id' => $line->assessmentId,
                        'calculated_at' => $now,
                    ],
                );
            }
        });

        return $lines;
    }

    /**
     * Hitung tanpa menyimpan — dipakai halaman profil agar butir yang memenuhi juga tampil.
     *
     * @return list<GapLine>
     */
    public function linesFor(Employee $employee): array
    {
        $standard = JobStandard::forPosition($employee->position);
        if ($standard === null) {
            return [];
        }

        $required = $standard->items->mapWithKeys(fn ($item) => [$item->competency_id => $item->required_level])->all();
        $assessments = Assessment::query()
            ->select(Assessment::NON_SENSITIVE_COLUMNS)
            ->where('employee_id', $employee->id)
            ->whereIn('competency_id', array_keys($required))
            ->get();

        return $this->calculator->calculate($required, $assessments);
    }
}
