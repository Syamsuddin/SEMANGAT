<?php

namespace App\Modules\Kompetensi\Listeners;

use App\Modules\Integrasi\Models\Employee;
use App\Modules\Kompetensi\Actions\RecalculateGaps;
use App\Modules\Kompetensi\Events\AssessmentRecorded;
use Illuminate\Contracts\Queue\ShouldQueue;

/** KOM-06: gap dihitung ulang ≤ 1 menit setelah asesmen disetujui — lewat queue `default`. */
class RecalculateGapsOnAssessment implements ShouldQueue
{
    public string $queue = 'default';

    public function __construct(private readonly RecalculateGaps $recalculate) {}

    public function handle(AssessmentRecorded $event): void
    {
        $employee = Employee::query()->with('position')->find($event->employeeId);
        if ($employee !== null) {
            ($this->recalculate)($employee);
        }
    }
}
