<?php

namespace App\Modules\Integrasi\Console;

use App\Modules\Integrasi\Actions\RunEmployeeSync;
use App\Modules\Integrasi\Adapters\EmployeeFileImportAdapter;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/** docs/11: `php artisan semangat:sync employees [--file=<path>]` — jalan sinkron (tanpa queue). */
class SyncCommand extends Command
{
    protected $signature = 'semangat:sync {target : employees} {--file= : berkas XLSX/CSV lokal untuk impor} {--adapter= : timpa adaptor terkonfigurasi}';

    protected $description = 'Sinkron data pegawai/unit/jabatan dari sumber (INT-01/03)';

    public function handle(RunEmployeeSync $sync): int
    {
        if ($this->argument('target') !== 'employees') {
            $this->error('Target yang didukung: employees');

            return self::INVALID;
        }

        $ref = null;
        $adapter = (string) ($this->option('adapter') ?: config('semangat.employee_adapter'));

        if ($file = $this->option('file')) {
            if (! is_file($file)) {
                $this->error("Berkas tidak ditemukan: {$file}");

                return self::FAILURE;
            }
            $adapter = EmployeeFileImportAdapter::NAME;
            $ref = config('semangat.imports.path').'/'.Str::uuid().'.'.strtolower(pathinfo($file, PATHINFO_EXTENSION));
            Storage::disk((string) config('semangat.imports.disk'))->put($ref, (string) file_get_contents($file));
        } elseif ($adapter === 'file') {
            $this->error('Adaptor `file` butuh --file=<path>.');

            return self::INVALID;
        }

        $run = $sync($adapter, $ref);
        $this->line("Run #{$run->id}: {$run->status->value} — {$run->rows_ok} baris ok, {$run->rows_failed} gagal");
        foreach ($run->failures as $failure) {
            $this->line("  baris {$failure->row_number}: {$failure->error}");
        }
        if ($run->error) {
            $this->warn($run->error);
        }

        return $run->status->value === 'failed' ? self::FAILURE : self::SUCCESS;
    }
}
