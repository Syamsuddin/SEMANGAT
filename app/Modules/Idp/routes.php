<?php

use App\Modules\Idp\Http\Controllers\LearningPlanDownloadController;
use App\Modules\Idp\Livewire\IdpEditor;
use App\Modules\Idp\Livewire\TeamApprovalList;
use App\Modules\Idp\Livewire\UnlinkedIdpList;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->prefix('idp')->group(function () {
    Route::get('/', IdpEditor::class)->name('idp.plans.mine');
    Route::get('/tim', TeamApprovalList::class)->name('idp.team.index');
    Route::get('/tanpa-tautan', UnlinkedIdpList::class)->name('idp.unlinked.index');
    Route::get('/rencana/{idp}/unduh', LearningPlanDownloadController::class)->whereNumber('idp')->name('idp.plans.download');
});
