<?php

namespace App\Modules\Integrasi\Data;

/** Satu RHK dari sumber SKP (INT-02). Atribut minimal sesuai docs/07 — tidak ada nilai/predikat. */
final readonly class SkpRecord
{
    public function __construct(
        public int $rowNumber,
        public ?string $nip,
        public int $periodYear,
        public ?string $workResultCode,
        public ?string $description,
        public ?string $specialExpectation = null,
        public ?string $realization = null,
        public ?string $dialogueNotes = null,
        public ?string $realizationNotes = null,
    ) {}

    /** @return array<string, string|int|null> */
    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
