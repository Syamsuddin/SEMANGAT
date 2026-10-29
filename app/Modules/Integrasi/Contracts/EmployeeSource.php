<?php

namespace App\Modules\Integrasi\Contracts;

use App\Modules\Integrasi\Data\EmployeeRecord;

/**
 * Sumber data pegawai/unit/jabatan (INT-01). Hanya membaca — tidak ada metode tulis ke sumber.
 */
interface EmployeeSource
{
    /** Nilai kolom `source` pada baris ⇅, mis. `simpeg_api`, `employee_file`. */
    public function name(): string;

    /** true: pegawai sumber ini yang tidak ada di snapshot dinonaktifkan (docs/06 § 2 langkah 3). */
    public function isFullSnapshot(): bool;

    /** true: satu baris tidak valid → seluruh batch ditolak (impor berkas, INT-03). */
    public function rejectsWholeBatchOnError(): bool;

    /**
     * @param  string|null  $sourceRef  rujukan berkas/parameter (int_sync_runs.source_ref)
     * @return iterable<EmployeeRecord>
     */
    public function fetch(?string $sourceRef = null): iterable;
}
