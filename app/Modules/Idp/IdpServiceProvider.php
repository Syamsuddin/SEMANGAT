<?php

namespace App\Modules\Idp;

use App\Modules\Idp\Livewire\IdpEditor;
use App\Modules\Idp\Livewire\TeamApprovalList;
use App\Modules\Idp\Livewire\UnlinkedIdpList;
use App\Modules\Idp\Models\Idp;
use App\Modules\Idp\Policies\IdpPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

/** M06 IDP (modul poros): draf otomatis, 2–3 kompetensi, tautan SKP, persetujuan massal, Rencana Pembelajaran, check-in, penutupan. */
class IdpServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/Database/Migrations');
        $this->loadViewsFrom(__DIR__.'/resources/views', 'idp');
        Route::middleware('web')->group(__DIR__.'/routes.php');

        Gate::policy(Idp::class, IdpPolicy::class);

        Livewire::component('idp.idp-editor', IdpEditor::class);
        Livewire::component('idp.team-approval-list', TeamApprovalList::class);
        Livewire::component('idp.unlinked-idp-list', UnlinkedIdpList::class);
    }
}
