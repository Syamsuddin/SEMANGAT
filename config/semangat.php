<?php

use App\Modules\Integrasi\Adapters\EmployeeFileImportAdapter;
use App\Modules\Integrasi\Adapters\SimpegApiAdapter;
use App\Modules\Integrasi\Adapters\SkpFileImportAdapter;

return [

    /*
    | Binding adaptor sumber (docs/08 § Integrasi eksternal). Kunci = nilai kolom `source`.
    */
    'adapters' => [
        'employees' => [
            EmployeeFileImportAdapter::NAME => EmployeeFileImportAdapter::class,
            SimpegApiAdapter::NAME => SimpegApiAdapter::class,
        ],
        // INT-02: hanya baca. EkinerjaApiAdapter ditambahkan saat Q-02 terjawab.
        'skp' => [
            SkpFileImportAdapter::NAME => SkpFileImportAdapter::class,
        ],
    ],

    // Adaptor untuk sinkron terjadwal: `file` = tanpa jadwal (impor manual saja), `simpeg_api` = harian 02:00 WITA.
    'employee_adapter' => env('SEMANGAT_EMPLOYEE_ADAPTER', 'file'),

    'simpeg' => [
        'url' => env('SIMPEG_API_URL'),
        'token' => env('SIMPEG_API_TOKEN'),
    ],

    'imports' => [
        'disk' => env('SEMANGAT_IMPORT_DISK', 'local'),
        'path' => 'imports',
        'max_kb' => 10240,
    ],

    // Host e-Kinerja — hanya untuk penjaga INT-02 (tidak ada request non-GET ke host ini).
    'ekinerja' => [
        'url' => env('EKINERJA_API_URL', 'https://ekinerja.hulusungaiselatankab.go.id'),
    ],

    // Berkas laporan asesmen rinci (psikogram) — dienkripsi aplikasi; produksi: bucket terpisah semangat-sensitive (docs/21).
    'sensitive' => [
        'disk' => env('SEMANGAT_SENSITIVE_DISK', 'sensitive'),
        'max_kb' => 10240,
    ],

    // Agregat clo/dewan: sel dengan n < ambang disamarkan (docs/21) [ASUMSI].
    'aggregate_min_n' => 5,

    'verify_base_url' => env('SEMANGAT_VERIFY_BASE_URL', env('APP_URL')),

    'features' => [],
];
