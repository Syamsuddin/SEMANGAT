<?php

namespace App\Support\Enums;

/**
 * Label keyakinan data (KOM-05, docs/07 § Kolom keyakinan). Tampilan: komponen x-confidence (docs/26).
 * Urutan prioritas mesin kesenjangan: verified > provisional > indicative (docs/06 § 3).
 */
enum Confidence: string
{
    case Verified = 'verified';
    case Provisional = 'provisional';
    case Indicative = 'indicative';

    public function label(): string
    {
        return __('kompetensi.confidence.'.$this->value);
    }

    /** Angka lebih besar = lebih diprioritaskan. */
    public function rank(): int
    {
        return match ($this) {
            self::Verified => 3,
            self::Provisional => 2,
            self::Indicative => 1,
        };
    }
}
