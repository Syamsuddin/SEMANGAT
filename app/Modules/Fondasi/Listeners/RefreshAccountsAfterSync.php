<?php

namespace App\Modules\Fondasi\Listeners;

use App\Modules\Fondasi\Actions\SyncEmployeeAccounts;
use App\Modules\Integrasi\Events\EmployeesSynced;

/** docs/06 § 2 langkah 4: turunan dihitung ulang — akun, peran asn/atasan. Sinkron di dalam job sinkron. */
class RefreshAccountsAfterSync
{
    public function __construct(private readonly SyncEmployeeAccounts $accounts) {}

    public function handle(EmployeesSynced $event): void
    {
        ($this->accounts)();
    }
}
