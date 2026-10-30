<?php

namespace App\Modules\Kompetensi;

use Illuminate\Support\ServiceProvider;

/** M03 Profil kompetensi: kamus, SKJ, hasil uji, mesin kesenjangan, asesmen mandiri, agregasi. */
class KompetensiServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/Database/Migrations');
    }
}
