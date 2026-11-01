<?php

namespace App\Modules\Idp;

use Illuminate\Support\ServiceProvider;

/** M06 IDP (modul poros): draf otomatis, 2–3 kompetensi, tautan SKP, persetujuan massal, Rencana Pembelajaran, check-in, penutupan. */
class IdpServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/Database/Migrations');
    }
}
