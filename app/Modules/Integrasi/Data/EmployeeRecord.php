<?php

namespace App\Modules\Integrasi\Data;

/**
 * DTO satu baris pegawai dari sumber mana pun, sudah dinormalisasi ke kode internal
 * tetapi BELUM divalidasi (lihat EmployeeRecordValidator).
 */
final readonly class EmployeeRecord
{
    public function __construct(
        public int $rowNumber,
        public ?string $nip,
        public ?string $name,
        public ?string $employmentType,
        public ?string $status,
        public ?string $opdCode,
        public ?string $opdName,
        public ?string $unitCode,
        public ?string $unitName,
        public ?string $positionCode,
        public ?string $positionName,
        public ?string $positionType,
        public ?string $positionTier,
        public ?string $gradeClass,
        public ?string $positionFamily,
        public ?string $supervisorNip,
        public ?string $email,
        public ?string $phone,
        public ?string $rawError = null,
    ) {}

    /** @return array<string, string|int|null> */
    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
