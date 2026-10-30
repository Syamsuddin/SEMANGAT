<?php

namespace App\Modules\Integrasi\Console;

use App\Modules\Fondasi\Models\User;
use App\Modules\Integrasi\Actions\RunSkpImport;
use App\Modules\Integrasi\Adapters\SkpFileImportAdapter;
use App\Modules\Kompetensi\Actions\ImportCompetencyFile;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * docs/11: `php artisan semangat:import skp <path.xlsx> --period=2027` (INT-02) dan
 * `semangat:import kamus|skj|hasil-uji <path> --by=<nip/username>` (KOM-01/02/03, lewat Action publik M03).
 */
class ImportCommand extends Command
{
    protected $signature = 'semangat:import {target : skp|kamus|skj|hasil-uji} {path : berkas XLSX/CSV lokal}
        {--period= : tahun periode SKP (wajib untuk skp)} {--by= : username pengelola yang menjalankan (wajib untuk kamus/skj/hasil-uji)}';

    protected $description = 'Impor berkas terkendali: SKP (baca saja) atau data kompetensi';

    public function handle(RunSkpImport $skp, ImportCompetencyFile $competency): int
    {
        $target = (string) $this->argument('target');
        $file = (string) $this->argument('path');
        if (! in_array($target, ['skp', ...array_keys(ImportCompetencyFile::COLUMNS)], true)) {
            $this->error('Target yang didukung: skp, kamus, skj, hasil-uji');

            return self::INVALID;
        }
        if (! is_file($file)) {
            $this->error("Berkas tidak ditemukan: {$file}");

            return self::FAILURE;
        }

        $ref = config('semangat.imports.path').'/'.Str::uuid().'.'.strtolower(pathinfo($file, PATHINFO_EXTENSION));
        Storage::disk((string) config('semangat.imports.disk'))->put($ref, (string) file_get_contents($file));

        return $target === 'skp' ? $this->importSkp($skp, $ref) : $this->importCompetency($competency, $target, $ref);
    }

    private function importSkp(RunSkpImport $skp, string $ref): int
    {
        $period = (string) $this->option('period');
        if (preg_match('/^\d{4}$/', $period) !== 1) {
            $this->error('--period=<tahun> wajib untuk impor SKP.');

            return self::INVALID;
        }

        $run = $skp(SkpFileImportAdapter::NAME, (int) $period, $ref);
        $this->line("Run #{$run->id}: {$run->status->value} — {$run->rows_ok} baris ok, {$run->rows_failed} gagal");
        foreach ($run->failures as $failure) {
            $this->line("  baris {$failure->row_number}: {$failure->error}");
        }
        if ($run->error) {
            $this->warn($run->error);
        }

        return $run->status->value === 'failed' ? self::FAILURE : self::SUCCESS;
    }

    private function importCompetency(ImportCompetencyFile $import, string $kind, string $ref): int
    {
        $by = User::query()->where('username', (string) $this->option('by'))->first();
        if ($by === null || ! $by->can('competency.manage')) {
            $this->error('--by=<username> wajib: pengelola aktif yang berwenang mengimpor data kompetensi.');

            return self::INVALID;
        }

        $report = $import($kind, $ref, $by);
        if ($report->succeeded()) {
            $this->info("{$report->rowsOk} baris diimpor.");

            return self::SUCCESS;
        }
        $this->error($report->error ?? __('kompetensi.import.rejected', ['count' => count($report->errors)]));
        foreach ($report->errors as $row => $messages) {
            $this->line("  baris {$row}: ".implode('; ', $messages));
        }

        return self::FAILURE;
    }
}
