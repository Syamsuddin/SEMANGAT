<?php

namespace App\Modules\Idp\Documents;

use App\Modules\Fondasi\Enums\DocumentType;
use App\Modules\Idp\Models\Idp;
use App\Modules\Idp\Services\CompetentExpectation;
use App\Support\Documents\DocumentContent;
use Illuminate\Database\Eloquent\Model;

/** IDP-05: isi Rencana Pembelajaran — kompetensi prioritas, tautan RHK, aktivitas 10:20:70, target JP, teks "Kompeten" per jenjang. */
class LearningPlanContent implements DocumentContent
{
    public function __construct(private readonly CompetentExpectation $expectation) {}

    public function type(): DocumentType
    {
        return DocumentType::LearningPlan;
    }

    public function view(): string
    {
        return 'idp::pdf.learning-plan';
    }

    /** @param  Idp  $documentable */
    public function data(Model $documentable): array
    {
        $documentable->load(['employee.position', 'employee.opd', 'supervisor', 'items.competency', 'items.workResult', 'items.mentor', 'items.activities']);

        return [
            'idp' => $documentable,
            'expectation' => $this->expectation->for($documentable->employee->position?->tier),
        ];
    }

    public function readyUrl(Model $documentable): string
    {
        return route('idp.plans.mine');
    }
}
