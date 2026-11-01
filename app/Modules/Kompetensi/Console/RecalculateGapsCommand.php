<?php

namespace App\Modules\Kompetensi\Console;

use App\Modules\Integrasi\Models\Employee;
use App\Modules\Kompetensi\Actions\RecalculateGaps;
use App\Modules\Kompetensi\Jobs\RecalculateGapsJob;
use Illuminate\Console\Command;

/** docs/11: `php artisan semangat:gaps:recalculate [--employee=<nip>]` — tanpa queue. */
class RecalculateGapsCommand extends Command
{
    protected $signature = 'semangat:gaps:recalculate {--employee= : NIP satu pegawai}';

    protected $description = 'Hitung ulang kesenjangan kompetensi (KOM-04)';

    public function handle(RecalculateGaps $recalculate): int
    {
        if ($nip = $this->option('employee')) {
            $employee = Employee::query()->with('position')->where('nip', $nip)->first();
            if ($employee === null) {
                $this->error("Pegawai tidak ditemukan: {$nip}");

                return self::FAILURE;
            }
            $lines = $recalculate($employee);
            $this->line(count(array_filter($lines, fn ($l) => $l->hasGap())).' kesenjangan dari '.count($lines).' butir SKJ.');

            return self::SUCCESS;
        }

        RecalculateGapsJob::dispatchSync();
        $this->info('Kesenjangan seluruh pegawai dihitung ulang.');

        return self::SUCCESS;
    }
}
