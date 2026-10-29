<?php

namespace App\Modules\Integrasi\Jobs;

use App\Modules\Integrasi\Actions\RunEmployeeSync;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/** docs/06 § 2: retry 3× backoff; tiap percobaan tercatat sebagai run tersendiri. */
class SyncEmployees implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** @var list<int> */
    public array $backoff = [60, 300, 900];

    public function __construct(public string $adapter, public ?string $sourceRef = null, public ?int $triggeredBy = null)
    {
        $this->onQueue('sync');
    }

    public function handle(RunEmployeeSync $sync): void
    {
        $sync($this->adapter, $this->sourceRef, $this->triggeredBy);
    }
}
