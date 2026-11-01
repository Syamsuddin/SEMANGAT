<?php

namespace App\Modules\Kompetensi;

use App\Modules\Integrasi\Events\EmployeesSynced;
use App\Modules\Kompetensi\Console\RecalculateGapsCommand;
use App\Modules\Kompetensi\Events\AssessmentRecorded;
use App\Modules\Kompetensi\Jobs\RecalculateGapsJob;
use App\Modules\Kompetensi\Listeners\RecalculateGapsOnAssessment;
use App\Modules\Kompetensi\Livewire\AssessmentDetail;
use App\Modules\Kompetensi\Livewire\CompetencyImport;
use App\Modules\Kompetensi\Livewire\CompetencyProfile;
use App\Modules\Kompetensi\Livewire\GapAggregate;
use App\Modules\Kompetensi\Livewire\SelfAssessmentForm;
use App\Modules\Kompetensi\Livewire\SelfAssessmentValidation;
use App\Modules\Kompetensi\Livewire\TeamGapSummary;
use App\Modules\Kompetensi\Models\Assessment;
use App\Modules\Kompetensi\Policies\AssessmentPolicy;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

/** M03 Profil kompetensi: kamus, SKJ, hasil uji, mesin kesenjangan, asesmen mandiri, agregasi. */
class KompetensiServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/Database/Migrations');
        $this->loadViewsFrom(__DIR__.'/resources/views', 'kompetensi');
        Route::middleware('web')->group(__DIR__.'/routes.php');

        Gate::policy(Assessment::class, AssessmentPolicy::class);

        Event::listen(AssessmentRecorded::class, RecalculateGapsOnAssessment::class);
        // docs/06 § 2 langkah 4: jabatan/status berubah setelah sinkron → kesenjangan dihitung ulang (queue).
        Event::listen(EmployeesSynced::class, fn () => RecalculateGapsJob::dispatch()->afterCommit());

        Livewire::component('kompetensi.competency-profile', CompetencyProfile::class);
        Livewire::component('kompetensi.team-gap-summary', TeamGapSummary::class);
        Livewire::component('kompetensi.self-assessment-form', SelfAssessmentForm::class);
        Livewire::component('kompetensi.self-assessment-validation', SelfAssessmentValidation::class);
        Livewire::component('kompetensi.assessment-detail', AssessmentDetail::class);
        Livewire::component('kompetensi.gap-aggregate', GapAggregate::class);
        Livewire::component('kompetensi.competency-import', CompetencyImport::class);

        if ($this->app->runningInConsole()) {
            $this->commands([RecalculateGapsCommand::class]);
        }
    }
}
