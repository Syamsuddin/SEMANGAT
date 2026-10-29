<?php

use App\Modules\Integrasi\Adapters\EmployeeFileImportAdapter;
use App\Modules\Integrasi\Adapters\SimpegApiAdapter;

return [

    /*
    | Binding adaptor sumber (docs/08 § Integrasi eksternal). Kunci = nilai kolom `source`.
    */
    'adapters' => [
        'employees' => [
            EmployeeFileImportAdapter::NAME => EmployeeFileImportAdapter::class,
            SimpegApiAdapter::NAME => SimpegApiAdapter::class,
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

    'verify_base_url' => env('SEMANGAT_VERIFY_BASE_URL', env('APP_URL')),

    'features' => [],
];
