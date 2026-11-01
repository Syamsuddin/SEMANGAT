<?php

use App\Modules\Kompetensi\Livewire\AssessmentDetail;
use App\Modules\Kompetensi\Livewire\CompetencyImport;
use App\Modules\Kompetensi\Livewire\CompetencyProfile;
use App\Modules\Kompetensi\Livewire\GapAggregate;
use App\Modules\Kompetensi\Livewire\SelfAssessmentForm;
use App\Modules\Kompetensi\Livewire\SelfAssessmentValidation;
use App\Modules\Kompetensi\Livewire\TeamGapSummary;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->prefix('kompetensi')->group(function () {
    Route::get('/', CompetencyProfile::class)->name('kompetensi.profile.show');
    Route::get('/pegawai/{employee}', CompetencyProfile::class)->whereNumber('employee')->name('kompetensi.profile.employee');
    Route::get('/tim', TeamGapSummary::class)->name('kompetensi.team.index');
    Route::get('/asesmen-mandiri', SelfAssessmentForm::class)->name('kompetensi.self.create');
    Route::get('/validasi', SelfAssessmentValidation::class)->name('kompetensi.validation.index');
    Route::get('/asesmen/{assessment}', AssessmentDetail::class)->whereNumber('assessment')->name('kompetensi.assessments.show');
    Route::get('/agregat', GapAggregate::class)->name('kompetensi.aggregate.index');
    Route::get('/impor', CompetencyImport::class)->name('kompetensi.import');
});
