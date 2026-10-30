<?php

namespace App\Modules\Kompetensi;

use App\Modules\Integrasi\Events\EmployeesSynced;
use App\Modules\Kompetensi\Console\RecalculateGapsCommand;
use App\Modules\Kompetensi\Events\AssessmentRecorded;
use App\Modules\Kompetensi\Jobs\RecalculateGapsJob;
use App\Modules\Kompetensi\Listeners\RecalculateGapsOnAssessment;
use App\Modules\Kompetensi\Models\Assessment;
use App\Modules\Kompetensi\Policies\AssessmentPolicy;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

/** M03 Profil kompetensi: kamus, SKJ, hasil uji, mesin kesenjangan, asesmen mandiri, agregasi. */
class KompetensiServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/Database/Migrations');

        Gate::policy(Assessment::class, AssessmentPolicy::class);

        Event::listen(AssessmentRecorded::class, RecalculateGapsOnAssessment::class);
        // docs/06 § 2 langkah 4: jabatan/status berubah setelah sinkron → kesenjangan dihitung ulang (queue).
        Event::listen(EmployeesSynced::class, fn () => RecalculateGapsJob::dispatch()->afterCommit());

        if ($this->app->runningInConsole()) {
            $this->commands([RecalculateGapsCommand::class]);
        }
    }
}
