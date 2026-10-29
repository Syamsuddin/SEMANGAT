<?php

namespace App\Modules\Integrasi\Database\Factories;

use App\Modules\Integrasi\Enums\SyncStatus;
use App\Modules\Integrasi\Models\SyncRun;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<SyncRun> */
class SyncRunFactory extends Factory
{
    protected $model = SyncRun::class;

    public function definition(): array
    {
        return [
            'adapter' => 'employee_file',
            'source_ref' => 'imports/contoh.csv',
            'started_at' => now()->subMinutes(5),
            'finished_at' => now(),
            'status' => SyncStatus::Success,
            'rows_ok' => 3,
            'rows_failed' => 0,
        ];
    }

    public function failed(int $rowsFailed = 1, string $error = 'Berkas ditolak'): static
    {
        return $this->state(fn () => ['status' => SyncStatus::Failed, 'rows_ok' => 0, 'rows_failed' => $rowsFailed, 'error' => $error]);
    }
}
