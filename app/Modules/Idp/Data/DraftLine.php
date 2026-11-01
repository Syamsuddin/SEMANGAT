<?php

namespace App\Modules\Idp\Data;

/** Satu usulan butir draf IDP (IDP-01). */
final readonly class DraftLine
{
    public function __construct(
        public int $competencyId,
        public ?int $gapId,
        public int $startLevel,
        public int $targetLevel,
        public ?int $workResultId,
    ) {}
}
