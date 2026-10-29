<?php

namespace App\Modules\Integrasi\Events;

use App\Modules\Integrasi\Models\SyncRun;
use Illuminate\Foundation\Events\Dispatchable;

/** Dipicu setelah upsert pegawai berhasil; Fondasi menghitung ulang akun & peran turunan. */
class EmployeesSynced
{
    use Dispatchable;

    public function __construct(public readonly SyncRun $run) {}
}
