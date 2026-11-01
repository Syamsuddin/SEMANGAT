<?php

namespace App\Modules\Idp\Services;

use App\Modules\Idp\Data\DraftLine;
use App\Modules\Idp\Models\Idp;
use App\Modules\Integrasi\Models\Employee;
use App\Modules\Integrasi\Models\SkpWorkResult;
use App\Modules\Kompetensi\Models\Gap;

/**
 * IDP-01: draf ≤3 kompetensi dari kesenjangan terbesar + usulan tautan RHK periode berjalan.
 * Posisi kotak talenta (INT-04) dan saldo JP (M12) menyusul pada fasenya (F5/F4).
 */
class DraftBuilder
{
    public function __construct(private readonly WorkResultMatcher $matcher) {}

    /** @return list<DraftLine> */
    public function for(Employee $employee, int $periodYear): array
    {
        $workResults = $this->workResults($employee, $periodYear);

        return Gap::query()
            ->where('employee_id', $employee->id)
            ->where('gap', '>', 0)
            ->with('competency:id,name,description')
            ->orderByDesc('gap')->orderByDesc('required_level')->orderBy('competency_id')
            ->limit(Idp::MAX_ITEMS)
            ->get()
            ->map(fn (Gap $gap) => new DraftLine(
                competencyId: $gap->competency_id,
                gapId: $gap->id,
                startLevel: $gap->required_level - $gap->gap,
                targetLevel: $gap->required_level,
                workResultId: $this->matcher->suggest(trim($gap->competency->name.' '.$gap->competency->description), $workResults),
            ))
            ->values()
            ->all();
    }

    /**
     * RHK SKP pegawai pada periode (baca saja, INT-02).
     *
     * @return array<int, string> id => deskripsi
     */
    public function workResults(Employee $employee, int $periodYear): array
    {
        return SkpWorkResult::query()
            ->whereHas('summary', fn ($q) => $q->where('employee_id', $employee->id)->where('period_year', $periodYear))
            ->orderBy('code')
            ->pluck('description', 'id')
            ->map(fn ($d) => (string) $d)
            ->all();
    }
}
