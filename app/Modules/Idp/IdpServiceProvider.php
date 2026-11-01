<?php

namespace App\Modules\Idp;

use App\Modules\Idp\Models\Idp;
use App\Modules\Idp\Policies\IdpPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

/** M06 IDP (modul poros): draf otomatis, 2–3 kompetensi, tautan SKP, persetujuan massal, Rencana Pembelajaran, check-in, penutupan. */
class IdpServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/Database/Migrations');
        $this->loadViewsFrom(__DIR__.'/resources/views', 'idp');

        Gate::policy(Idp::class, IdpPolicy::class);
    }
}
