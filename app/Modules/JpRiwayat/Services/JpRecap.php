<?php

namespace App\Modules\JpRiwayat\Services;

use App\Modules\Fondasi\Enums\Role;
use App\Modules\Fondasi\Models\User;
use App\Modules\Integrasi\Models\Employee;
use App\Support\Scope\DataScope;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * JPR-06: rekap pemenuhan 20 JP per OPD & kabupaten atas pegawai aktif (yang belum punya catatan dihitung 0 JP).
 * Koord. OPD → baris pegawai OPD-nya (O). Pengelola/auditor → per OPD seluruh kabupaten (K/B).
 * clo/dewan → per OPD agregat (A/R); sel dengan n < ambang disamarkan (docs/21) `[ASUMSI]` n=5, ditambah supresi
 * komplementer agar OPD tersamar tidak dapat dihitung balik dari total dikurangi baris yang tampak.
 * Peran bersifat kumulatif: koord. OPD yang juga clo/dewan/pengelola tetap melihat baris pegawai OPD penugasannya.
 */
class JpRecap
{
    /** Sel disamarkan karena n < ambang. */
    public const MASK_SMALL = 'small';

    /** Sel n ≥ ambang ikut disamarkan untuk melindungi sel kecil (supresi komplementer). */
    public const MASK_COMPLEMENT = 'complement';

    /**
     * @return array{scope: string, masked: bool, min_n: int, year: int, target: int,
     *     total: array{employees: int|null, fulfilled: int|null, avg_jp: float|null, mask: string|null},
     *     opds: list<array{name: string, employees: int|null, fulfilled: int|null, avg_jp: float|null, mask: string|null}>,
     *     employees: list<array{nip: string, name: string, opd: string, jp: float, fulfilled: bool}>}
     */
    public function forUser(User $user, int $year): array
    {
        $scope = DataScope::for($user);
        $regency = $user->hasAnyRole([Role::Pengelola->value, Role::Auditor->value, Role::Clo->value, Role::DewanPengarah->value]);
        $masked = $regency && ! $scope->seesAllEmployees();
        $opdIds = $scope->opdIds();
        $minN = (int) config('semangat.aggregate_min_n', 5);
        $target = (int) config('semangat.jp.annual_target', 20);

        $base = $this->base($this->employeesInRecap($user, $scope, $regency, $opdIds), $year);
        $metrics = 'COUNT(*) as employees, SUM(COALESCE(j.jp, 0) >= ?) as fulfilled, AVG(COALESCE(j.jp, 0)) as avg_jp';

        $opds = (clone $base)
            ->join('hr_org_units as o', 'o.id', '=', 'hr_employees.opd_id')
            ->groupBy('o.id', 'o.name')
            ->orderBy('o.name')
            ->selectRaw('o.name, '.$metrics, [$target])
            ->get()
            ->map(fn (object $row) => ['name' => (string) $row->name] + $this->metrics($row))
            ->values()->all();

        $totalRow = (clone $base)->selectRaw($metrics, [$target])->first();
        $total = $totalRow === null ? ['employees' => 0, 'fulfilled' => 0, 'avg_jp' => 0.0, 'mask' => null] : $this->metrics($totalRow);

        if ($masked) {
            [$opds, $total] = $this->suppress($opds, $total, $minN);
        }

        // Baris individu hanya untuk OPD penugasan koord. OPD (O) — rekap kabupaten tidak memuat identitas.
        $employees = $opdIds === [] ? [] : $this->base(Employee::query()->visibleTo($user)->whereIn('hr_employees.opd_id', $opdIds), $year)
            ->join('hr_org_units as o', 'o.id', '=', 'hr_employees.opd_id')
            ->orderBy('o.name')->orderBy('hr_employees.name')
            ->selectRaw('hr_employees.nip, hr_employees.name, o.name as opd, COALESCE(j.jp, 0) as jp')
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
            'total' => $total,
            'opds' => $opds,
            'employees' => $employees,
        ];
    }

    /**
     * Pegawai yang dihitung: cakupan visibleTo (O untuk koord. OPD, K/B untuk pengelola/auditor). clo/dewan (A/R) adalah
     * pengecualian eksplisit — agregat seluruh kabupaten tanpa baris individu, tetap tertutup bagi akun nonaktif.
     *
     * @param  list<int>  $opdIds
     * @return EloquentBuilder<Employee>
     */
    private function employeesInRecap(User $user, DataScope $scope, bool $regency, array $opdIds): EloquentBuilder
    {
        if ($regency && ! $scope->seesAllEmployees()) {
            return Employee::query()->when(! $user->is_active, fn (EloquentBuilder $q) => $q->whereRaw('1 = 0'));
        }

        $query = Employee::query()->visibleTo($user);

        return $regency ? $query : $query->whereIn('hr_employees.opd_id', $opdIds === [] ? [0] : $opdIds);
    }

    /** @param  EloquentBuilder<Employee>  $employees */
    private function base(EloquentBuilder $employees, int $year): Builder
    {
        $verified = DB::table('out_jp_records')
            ->where('year', $year)
            ->where('status', 'verified')
            ->groupBy('employee_id')
            ->selectRaw('employee_id, SUM(jp) as jp');

        return $employees->toBase()
            ->leftJoinSub($verified, 'j', 'j.employee_id', '=', 'hr_employees.id')
            ->where('hr_employees.status', 'active');
    }

    /** @return array{employees: int, fulfilled: int, avg_jp: float, mask: null} */
    private function metrics(object $row): array
    {
        return [
            'employees' => (int) $row->employees,
            'fulfilled' => (int) $row->fulfilled,
            'avg_jp' => round((float) $row->avg_jp, 1),
            'mask' => null,
        ];
    }

    /**
     * Samarkan OPD dengan n < ambang, lalu OPD tampak terkecil sampai sisa (total − baris tampak) mencakup ≥ 2 OPD dan
     * ≥ ambang pegawai. Selalu tercapai bila total ≥ ambang (paling buruk semua OPD disamarkan), sehingga total tetap tampil.
     *
     * @param  list<array{name: string, employees: int|null, fulfilled: int|null, avg_jp: float|null, mask: string|null}>  $opds
     * @param  array{employees: int|null, fulfilled: int|null, avg_jp: float|null, mask: string|null}  $total
     * @return array{0: list<array{name: string, employees: int|null, fulfilled: int|null, avg_jp: float|null, mask: string|null}>, 1: array{employees: int|null, fulfilled: int|null, avg_jp: float|null, mask: string|null}}
     */
    private function suppress(array $opds, array $total, int $minN): array
    {
        $hide = fn (array $cell, string $mask) => ['employees' => null, 'fulfilled' => null, 'avg_jp' => null, 'mask' => $mask] + $cell;

        if ((int) $total['employees'] < $minN) {
            return [array_map(fn (array $row) => $hide($row, self::MASK_SMALL), $opds), $hide($total, self::MASK_SMALL)];
        }

        $small = array_keys(array_filter($opds, fn (array $row) => (int) $row['employees'] < $minN));
        if ($small === []) {
            return [$opds, $total];
        }

        $visible = array_diff(array_keys($opds), $small);
        usort($visible, fn (int $a, int $b) => [$opds[$a]['employees'], $opds[$a]['name']] <=> [$opds[$b]['employees'], $opds[$b]['name']]);

        $hidden = $small;
        $complement = [];
        $exposed = fn (array $hidden) => count($hidden) < 2 || array_sum(array_map(fn (int $i) => (int) $opds[$i]['employees'], $hidden)) < $minN;
        while ($exposed($hidden) && $visible !== []) {
            $next = array_shift($visible);
            $hidden[] = $next;
            $complement[] = $next;
        }

        foreach ($small as $i) {
            $opds[$i] = $hide($opds[$i], self::MASK_SMALL);
        }
        foreach ($complement as $i) {
            $opds[$i] = $hide($opds[$i], self::MASK_COMPLEMENT);
        }

        return [$opds, $total];
    }
}
