<?php

use App\Modules\Fondasi\Enums\Permission;
use App\Modules\Integrasi\Http\Controllers\EmployeeTemplateController;
use App\Modules\Integrasi\Livewire\EmployeeImport;
use App\Modules\Integrasi\Livewire\HealthPanel;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->prefix('integrasi')->group(function () {
    Route::get('/', HealthPanel::class)->name('integrasi.panel');
    Route::get('/impor-pegawai', EmployeeImport::class)->name('integrasi.import');
    Route::get('/templat-pegawai.csv', EmployeeTemplateController::class)
        ->middleware('can:'.Permission::IntegrationManage->value)
        ->name('integrasi.template');
});
