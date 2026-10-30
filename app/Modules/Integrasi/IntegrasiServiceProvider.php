<?php

namespace App\Modules\Integrasi;

use App\Modules\Integrasi\Console\ImportCommand;
use App\Modules\Integrasi\Console\SyncCommand;
use App\Modules\Integrasi\Livewire\EmployeeImport;
use App\Modules\Integrasi\Livewire\HealthPanel;
use App\Modules\Integrasi\Models\Employee;
use App\Modules\Integrasi\Models\SyncRun;
use App\Modules\Integrasi\Policies\EmployeePolicy;
use App\Modules\Integrasi\Policies\SyncRunPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

/** M02 Integrasi: adaptor sumber, sinkron, panel kesehatan. Pemilik tabel ⇅ hr_*. */
class IntegrasiServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/Database/Migrations');
        $this->loadViewsFrom(__DIR__.'/resources/views', 'integrasi');
        Route::middleware('web')->group(__DIR__.'/routes.php');

        Gate::policy(Employee::class, EmployeePolicy::class);
        Gate::policy(SyncRun::class, SyncRunPolicy::class);

        Livewire::component('integrasi.health-panel', HealthPanel::class);
        Livewire::component('integrasi.employee-import', EmployeeImport::class);

        if ($this->app->runningInConsole()) {
            $this->commands([SyncCommand::class, ImportCommand::class]);
        }
    }
}
