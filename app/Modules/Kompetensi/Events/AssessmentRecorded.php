<?php

namespace App\Modules\Kompetensi\Events;

use Illuminate\Foundation\Events\Dispatchable;

/** Hasil penilaian baru/berubah yang memengaruhi kesenjangan (docs/08: AssessmentRecorded → RecalculateGaps). */
class AssessmentRecorded
{
    use Dispatchable;

    public function __construct(public readonly int $employeeId) {}
}
