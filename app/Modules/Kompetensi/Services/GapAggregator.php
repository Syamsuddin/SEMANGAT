<?php

namespace App\Modules\Kompetensi\Services;

use App\Modules\Fondasi\Enums\Role;
use App\Modules\Fondasi\Models\User;
use App\Support\Scope\DataScope;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * KOM-08: agregasi kesenjangan per OPD & kabupaten untuk M05. Tidak pernah mengembalikan baris individu.
 * Cakupan: koordinator OPD → OPD penugasannya (O); pengelola/auditor → kabupaten (B);
 * clo/dewan → kabupaten agregat (A/R), sel dengan n < ambang disamarkan (docs/21) `[ASUMSI]` n=5.
 */
class GapAggregator
{
    /**
     * @return array{scope: string, masked: bool, min_n: int, employees: int|null,
     *     competencies: list<array{code: string, name: string, employees: int|null, avg_gap: float|null, verified: int|null, provisional: int|null, indicative: int|null}>,
     *     opds: list<array{name: string, employees: int|null, gaps: int|null}>}
     */
    public function forUser(User $user): array
    {
        $scope = DataScope::for($user);
        $regency = $user->hasAnyRole([Role::Pengelola->value, Role::Auditor->value, Role::Clo->value, Role::DewanPengarah->value]);
        $opdIds = $regency ? null : $scope->opdIds();
        $masked = ! $scope->seesAllEmployees() && $user->hasAnyRole([Role::Clo->value, Role::DewanPengarah->value]) && $opdIds === null;
        $minN = (int) config('semangat.aggregate_min_n', 5);

        $mask = fn (?int $n, mixed $value) => $masked && ($n ?? 0) < $minN ? null : $value;

        $competencies = $this->base($opdIds)
            ->join('cmp_competencies as c', 'c.id', '=', 'g.competency_id')
            ->groupBy('c.id', 'c.code', 'c.name')
            ->orderByDesc(DB::raw('COUNT(DISTINCT g.employee_id)'))
            ->orderBy('c.code')
            ->selectRaw("c.code, c.name, COUNT(DISTINCT g.employee_id) as employees, AVG(g.gap) as avg_gap,
                SUM(g.confidence = 'verified') as verified, SUM(g.confidence = 'provisional') as provisional,
                SUM(g.confidence = 'indicative') as indicative")
            ->get()
            ->map(function (object $row) use ($mask) {
                $n = (int) $row->employees;

                return [
                    'code' => (string) $row->code,
                    'name' => (string) $row->name,
                    'employees' => $mask($n, $n),
                    'avg_gap' => $mask($n, round((float) $row->avg_gap, 1)),
                    'verified' => $mask($n, (int) $row->verified),
                    'provisional' => $mask($n, (int) $row->provisional),
                    'indicative' => $mask($n, (int) $row->indicative),
                ];
            })->values()->all();

        $opds = $this->base($opdIds)
            ->join('hr_org_units as o', 'o.id', '=', 'e.opd_id')
            ->groupBy('o.id', 'o.name')
            ->orderBy('o.name')
            ->selectRaw('o.name, COUNT(DISTINCT g.employee_id) as employees, COUNT(*) as gaps')
            ->get()
            ->map(function (object $row) use ($mask) {
                $n = (int) $row->employees;

                return ['name' => (string) $row->name, 'employees' => $mask($n, $n), 'gaps' => $mask($n, (int) $row->gaps)];
            })->values()->all();

        $total = (int) $this->base($opdIds)->count(DB::raw('DISTINCT g.employee_id'));

        return [
            'scope' => $regency ? 'regency' : 'opd',
            'masked' => $masked,
            'min_n' => $minN,
            'employees' => $mask($total, $total),
            'competencies' => $competencies,
            'opds' => $opds,
        ];
    }

    /** @param list<int>|null $opdIds null = seluruh kabupaten */
    private function base(?array $opdIds): Builder
    {
        return DB::table('cmp_gaps as g')
            ->join('hr_employees as e', 'e.id', '=', 'g.employee_id')
            ->where('e.status', 'active')
            ->when($opdIds !== null, fn (Builder $q) => $q->whereIn('e.opd_id', $opdIds === [] ? [0] : $opdIds));
    }
}
