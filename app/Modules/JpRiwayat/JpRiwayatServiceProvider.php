<?php

namespace App\Modules\JpRiwayat;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

/** M12 JP & riwayat: saldo 20 JP, unggah & verifikasi sertifikat luar, riwayat bangkom, Laporan Hasil Pembelajaran, rekap OPD/kabupaten. */
class JpRiwayatServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/Database/Migrations');
        $this->loadViewsFrom(__DIR__.'/resources/views', 'jp');
        Route::middleware('web')->group(__DIR__.'/routes.php');
    }
}
