<?php

namespace App\Modules\Kompetensi\Data;

use App\Support\Enums\Confidence;

/** Satu baris hasil mesin kesenjangan per butir SKJ (KOM-04). gap = 0 berarti memenuhi. */
final readonly class GapLine
{
    public function __construct(
        public int $competencyId,
        public int $requiredLevel,
        public ?int $actualLevel,
        public int $gap,
        public Confidence $confidence,
        public ?int $assessmentId,
    ) {}

    public function hasGap(): bool
    {
        return $this->gap > 0;
    }
}
