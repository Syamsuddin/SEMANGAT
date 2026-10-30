<?php

namespace App\Modules\Kompetensi\Enums;

/** Jenis kompetensi (docs/07 § cmp_competencies.type). */
enum CompetencyType: string
{
    case Managerial = 'managerial';
    case Sociocultural = 'sociocultural';
    case Technical = 'technical';

    public function label(): string
    {
        return __('kompetensi.types.'.$this->value);
    }
}
