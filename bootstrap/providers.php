<?php

use App\Modules\Fondasi\FondasiServiceProvider;
use App\Modules\Idp\IdpServiceProvider;
use App\Modules\Integrasi\IntegrasiServiceProvider;
use App\Modules\JpRiwayat\JpRiwayatServiceProvider;
use App\Modules\Kompetensi\KompetensiServiceProvider;
use App\Providers\AppServiceProvider;

return [
    AppServiceProvider::class,
    FondasiServiceProvider::class,
    IntegrasiServiceProvider::class,
    KompetensiServiceProvider::class,
    IdpServiceProvider::class,
    JpRiwayatServiceProvider::class,
];
