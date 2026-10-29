<?php

use App\Modules\Integrasi\Adapters\SimpegApiAdapter;
use App\Modules\Integrasi\Jobs\SyncEmployees;
use Illuminate\Support\Facades\Schedule;

// docs/06 § 2: sinkron harian 02:00 WITA [ASUMSI] — hanya bila adaptor API terkonfigurasi.
if (config('semangat.employee_adapter') === SimpegApiAdapter::NAME) {
    Schedule::job(new SyncEmployees(SimpegApiAdapter::NAME))
        ->dailyAt('02:00')
        ->timezone('Asia/Makassar')
        ->withoutOverlapping();
}
