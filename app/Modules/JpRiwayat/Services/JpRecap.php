<?php

namespace App\Modules\JpRiwayat\Services;

use App\Modules\Fondasi\Enums\Role;
use App\Modules\Fondasi\Models\User;
use App\Support\Scope\DataScope;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * JPR-06: rekap pemenuhan 20 JP per OPD & kabupaten atas pegawai aktif (yang belum punya catatan dihitung 0 JP).
 * Koord. OPD → baris pegawai OPD-nya (O). Pengelola/auditor → per OPD seluruh kabupaten (K/B).
 * clo/dewan → per OPD agregat (A/R); sel dengan n < ambang disamarkan (docs/21) `[ASUMSI]` n=5.
 */
class JpRecap
{
    /**
     * @return array{scope: string, masked: bool, min_n: int, year: int, target: int,
     *     total: array{employees: int|null, fulfilled: int|null, avg_jp: float|null},
     *     opds: list<array{name: string, employees: int|null, fulfilled: int|null, avg_jp: float|null}>,
     *     employees: list<array{nip: string, name: string, opd: string, jp: float, fulfilled: bool}>}
     */
    public function forUser(User $user, int $year): array
    {
        $scope = DataScope::for($user);
        $regency = $user->hasAnyRole([Role::Pengelola->value, Role::Auditor->value, Role::Clo->value, Role::DewanPengarah->value]);
        $opdIds = $regency ? null : $scope->opdIds();
        $masked = ! $scope->seesAllEmployees() && $user->hasAnyRole([Role::Clo->value, Role::DewanPengarah->value]) && $opdIds === null;
        $minN = (int) config('semangat.aggregate_min_n', 5);
        $target = (int) config('semangat.jp.annual_target', 20);

        $mask = fn (int $n, mixed $value) => $masked && $n < $minN ? null : $value;
        $summarise = function (object $row) use ($mask) {
            $n = (int) $row->employees;

            return [
                'employees' => $mask($n, $n),
                'fulfilled' => $mask($n, (int) $row->fulfilled),
                'avg_jp' => $mask($n, round((float) $row->avg_jp, 1)),
            ];
        };

        $base = $this->base($opdIds, $year);

        $opds = (clone $base)
            ->join('hr_org_units as o', 'o.id', '=', 'e.opd_id')
            ->groupBy('o.id', 'o.name')
            ->orderBy('o.name')
            ->selectRaw('o.name, COUNT(*) as employees, SUM(COALESCE(j.jp, 0) >= ?) as fulfilled, AVG(COALESCE(j.jp, 0)) as avg_jp', [$target])
            ->get()
            ->map(fn (object $row) => ['name' => (string) $row->name] + $summarise($row))
            ->values()->all();

        $total = (clone $base)
            ->selectRaw('COUNT(*) as employees, SUM(COALESCE(j.jp, 0) >= ?) as fulfilled, AVG(COALESCE(j.jp, 0)) as avg_jp', [$target])
            ->first();

        // Baris individu hanya untuk koord. OPD (O) — rekap kabupaten tidak memuat identitas.
        $employees = $regency ? [] : (clone $base)
            ->join('hr_org_units as o', 'o.id', '=', 'e.opd_id')
            ->orderBy('o.name')->orderBy('e.name')
            ->selectRaw('e.nip, e.name, o.name as opd, COALESCE(j.jp, 0) as jp')
            ->get()
            ->map(fn (object $row) => [
                'nip' => (string) $row->nip,
                'name' => (string) $row->name,
                'opd' => (string) $row->opd,
                'jp' => round((float) $row->jp, 1),
                'fulfilled' => (float) $row->jp >= $target,
            ])->values()->all();

        return [
            'scope' => $regency ? 'regency' : 'opd',
            'masked' => $masked,
            'min_n' => $minN,
            'year' => $year,
            'target' => $target,
            'total' => $total === null ? ['employees' => 0, 'fulfilled' => 0, 'avg_jp' => 0.0] : $summarise($total),
            'opds' => $opds,
            'employees' => $employees,
        ];
    }

    /** @param list<int>|null $opdIds null = seluruh kabupaten */
    private function base(?array $opdIds, int $year): Builder
    {
        $verified = DB::table('out_jp_records')
            ->where('year', $year)
            ->where('status', 'verified')
            ->groupBy('employee_id')
            ->selectRaw('employee_id, SUM(jp) as jp');

        return DB::table('hr_employees as e')
            ->leftJoinSub($verified, 'j', 'j.employee_id', '=', 'e.id')
            ->where('e.status', 'active')
            ->when($opdIds !== null, fn (Builder $q) => $q->whereIn('e.opd_id', $opdIds === [] ? [0] : $opdIds));
    }
}
