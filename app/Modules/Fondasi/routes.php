<?php

use App\Modules\Fondasi\Http\Controllers\AccountController;
use App\Modules\Fondasi\Http\Controllers\DocumentVerificationController;
use App\Modules\Fondasi\Http\Controllers\HomeRedirectController;
use App\Modules\Fondasi\Http\Controllers\ProfileController;
use App\Modules\Fondasi\Livewire\EmployeeIndex;
use App\Modules\Fondasi\Livewire\EmployeeShow;
use App\Modules\Fondasi\Livewire\Home;
use App\Modules\Fondasi\Livewire\NotificationCenter;
use App\Modules\Fondasi\Livewire\TeamList;
use Illuminate\Support\Facades\Route;

// FON-09: publik, tanpa login.
Route::get('/verifikasi/{uuid}', DocumentVerificationController::class)
    ->middleware('throttle:60,1')
    ->name('fondasi.documents.verify');

Route::middleware('auth')->group(function () {
    Route::get('/', HomeRedirectController::class)->name('fondasi.root');
    Route::get('/beranda', Home::class)->name('fondasi.home');
    Route::get('/profil', ProfileController::class)->name('fondasi.profile');
    Route::get('/akun', AccountController::class)->name('fondasi.account');
    Route::get('/notifikasi', NotificationCenter::class)->name('fondasi.notifications.index');
    Route::get('/tim', TeamList::class)->name('fondasi.team.index');
    Route::get('/pegawai', EmployeeIndex::class)->name('fondasi.employees.index');
    Route::get('/pegawai/{employee}', EmployeeShow::class)->whereNumber('employee')->name('fondasi.employees.show');
});
