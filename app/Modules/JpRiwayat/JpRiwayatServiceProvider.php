<?php

namespace App\Modules\JpRiwayat;

use App\Modules\JpRiwayat\Livewire\CertificateVerificationList;
use App\Modules\JpRiwayat\Livewire\JpOverview;
use App\Modules\JpRiwayat\Livewire\JpRecapReport;
use App\Modules\JpRiwayat\Models\JpRecord;
use App\Modules\JpRiwayat\Policies\JpRecordPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

/** M12 JP & riwayat: saldo 20 JP, unggah & verifikasi sertifikat luar, riwayat bangkom, Laporan Hasil Pembelajaran, rekap OPD/kabupaten. */
class JpRiwayatServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/Database/Migrations');
        $this->loadViewsFrom(__DIR__.'/resources/views', 'jp');
        Route::middleware('web')->group(__DIR__.'/routes.php');

        Gate::policy(JpRecord::class, JpRecordPolicy::class);

        Livewire::component('jp.jp-overview', JpOverview::class);
        Livewire::component('jp.certificate-verification-list', CertificateVerificationList::class);
        Livewire::component('jp.jp-recap-report', JpRecapReport::class);
    }
}
