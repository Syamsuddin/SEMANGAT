<?php

namespace App\Modules\JpRiwayat\Services;

use App\Modules\JpRiwayat\Enums\JpStatus;
use App\Modules\JpRiwayat\Models\JpRecord;

/**
 * JPR-01: saldo JP pegawai per tahun. Hanya `verified` dihitung ke target (docs/06 § 6); `pending` ditampilkan terpisah.
 */
class JpBalance
{
    /** @return array{year: int, verified: float, pending: float, target: int, fulfilled: bool, percent: int} */
    public function forEmployee(int $employeeId, int $year): array
    {
        $sums = JpRecord::query()
            ->where('employee_id', $employeeId)
            ->where('year', $year)
            ->whereIn('status', [JpStatus::Verified->value, JpStatus::Pending->value])
            ->groupBy('status')
            ->selectRaw('status, SUM(jp) as total')
            ->pluck('total', 'status');

        $verified = round((float) ($sums[JpStatus::Verified->value] ?? 0), 1);
        $target = $this->target();

        return [
            'year' => $year,
            'verified' => $verified,
            'pending' => round((float) ($sums[JpStatus::Pending->value] ?? 0), 1),
            'target' => $target,
            'fulfilled' => $verified >= $target,
            'percent' => (int) min(100, floor($verified / max(1, $target) * 100)),
        ];
    }

    public function target(): int
    {
        return (int) config('semangat.jp.annual_target', 20);
    }
}
