<?php

use App\Modules\JpRiwayat\Http\Controllers\CertificateDownloadController;
use App\Modules\JpRiwayat\Http\Controllers\LearningReportDownloadController;
use App\Modules\JpRiwayat\Livewire\CertificateVerificationList;
use App\Modules\JpRiwayat\Livewire\JpOverview;
use App\Modules\JpRiwayat\Livewire\JpRecapReport;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->prefix('jp')->group(function () {
    Route::get('/', JpOverview::class)->name('jp.records.mine');
    Route::get('/pegawai/{employee}', JpOverview::class)->whereNumber('employee')->name('jp.records.employee');
    Route::get('/verifikasi', CertificateVerificationList::class)->name('jp.certificates.index');
    Route::get('/rekap', JpRecapReport::class)->name('jp.recap.index');
    Route::get('/sertifikat/{record}/unduh', CertificateDownloadController::class)->whereNumber('record')->name('jp.certificates.download');
    Route::get('/laporan/{idp}/unduh', LearningReportDownloadController::class)->whereNumber('idp')->name('jp.reports.download');
});
