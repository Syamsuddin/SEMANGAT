<?php

use App\Modules\Fondasi\FondasiServiceProvider;
use App\Modules\Integrasi\IntegrasiServiceProvider;
use App\Providers\AppServiceProvider;

return [
    AppServiceProvider::class,
    FondasiServiceProvider::class,
    IntegrasiServiceProvider::class,
];
