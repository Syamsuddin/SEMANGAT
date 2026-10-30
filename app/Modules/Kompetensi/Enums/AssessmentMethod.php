<?php

namespace App\Modules\Kompetensi\Enums;

use App\Support\Enums\Confidence;

/** Metode penilaian (docs/07 § cmp_assessments.method); menentukan label keyakinan (KOM-05). */
enum AssessmentMethod: string
{
    case AssessmentCenter = 'assessment_center';
    case JfCompetencyTest = 'jf_competency_test';
    case SelfAssessment = 'self_assessment';
    case InferredSkp = 'inferred_skp';

    public function label(): string
    {
        return __('kompetensi.methods.'.$this->value);
    }

    /** Uji resmi → terverifikasi; asesmen mandiri (tervalidasi) → sementara; disimpulkan dari SKP → indikatif. */
    public function confidence(): Confidence
    {
        return match ($this) {
            self::AssessmentCenter, self::JfCompetencyTest => Confidence::Verified,
            self::SelfAssessment => Confidence::Provisional,
            self::InferredSkp => Confidence::Indicative,
        };
    }

    /**
     * Metode yang boleh diimpor pengelola sebagai hasil uji (bukan asesmen mandiri).
     *
     * @return list<self>
     */
    public static function importable(): array
    {
        return [self::AssessmentCenter, self::JfCompetencyTest, self::InferredSkp];
    }
}
