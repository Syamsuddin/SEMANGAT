<?php

namespace App\Modules\Kompetensi\Jobs;

use App\Modules\Integrasi\Models\Employee;
use App\Modules\Kompetensi\Actions\RecalculateGaps;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/** Hitung ulang kesenjangan sejumlah pegawai (null = semua) di queue — setelah impor SKJ/hasil uji atau sinkron pegawai. */
class RecalculateGapsJob implements ShouldQueue
{
    use Queueable;

    /** @param list<int>|null $employeeIds */
    public function __construct(public readonly ?array $employeeIds = null) {}

    public function handle(RecalculateGaps $recalculate): void
    {
        Employee::query()
            ->with('position')
            ->when($this->employeeIds !== null, fn ($q) => $q->whereIn('id', $this->employeeIds))
            ->chunkById(200, function ($employees) use ($recalculate) {
                foreach ($employees as $employee) {
                    $recalculate($employee);
                }
            });
    }
}
