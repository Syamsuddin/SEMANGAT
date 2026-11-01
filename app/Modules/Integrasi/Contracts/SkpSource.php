<?php

namespace App\Modules\Integrasi\Contracts;

use App\Modules\Integrasi\Data\SkpRecord;

/**
 * Sumber data SKP (INT-02) — BACA SAJA. Keputusan A3: arus satu arah dijamin tipe, bukan disiplin.
 * DILARANG menambah metode tulis/kirim/ubah; semua metode data berawalan `fetch` (dijaga tests/Arch).
 */
interface SkpSource
{
    /** Nilai kolom `source`, mis. `skp_file`, `ekinerja_api`. */
    public function name(): string;

    /**
     * Satu record per RHK beserta catatan dialog & realisasi akhir periode. Tanpa nilai/predikat.
     *
     * @return iterable<SkpRecord>
     */
    public function fetchWorkResults(int $periodYear, ?string $sourceRef = null): iterable;
}
