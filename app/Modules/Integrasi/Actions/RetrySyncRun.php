<?php

namespace App\Modules\Integrasi\Actions;

use App\Modules\Fondasi\Models\User;
use App\Modules\Integrasi\Enums\SyncStatus;
use App\Modules\Integrasi\Jobs\SyncEmployees;
use App\Modules\Integrasi\Models\SyncRun;
use Illuminate\Validation\ValidationException;

/** INT-08 tombol ulang: antrekan run baru dengan adaptor & rujukan yang sama. */
class RetrySyncRun
{
    public function __invoke(SyncRun $run, User $by): void
    {
        if (! in_array($run->status, [SyncStatus::Failed, SyncStatus::Partial], true)) {
            throw ValidationException::withMessages(['run' => __('integrasi.panel.not_retryable')]);
        }

        $run->failures()->whereNull('retried_at')->update(['retried_at' => now()]);

        SyncEmployees::dispatch($run->adapter, $run->source_ref, $by->id);
    }
}
