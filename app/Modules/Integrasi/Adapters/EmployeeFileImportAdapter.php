<?php

namespace App\Modules\Integrasi\Adapters;

use App\Modules\Integrasi\Contracts\EmployeeSource;
use App\Modules\Integrasi\Data\SourceRowMapper;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use OpenSpout\Reader\CSV\Options as CsvOptions;
use OpenSpout\Reader\CSV\Reader as CsvReader;
use OpenSpout\Reader\XLSX\Reader as XlsxReader;

/**
 * INT-03: impor berkas templat pegawai (XLSX/CSV). Baris 1 = judul kolom (SourceRowMapper::COLUMNS).
 * Tidak dianggap snapshot penuh; satu baris tidak valid → seluruh berkas ditolak.
 */
class EmployeeFileImportAdapter implements EmployeeSource
{
    public const NAME = 'employee_file';

    public function name(): string
    {
        return self::NAME;
    }

    public function isFullSnapshot(): bool
    {
        return false;
    }

    public function rejectsWholeBatchOnError(): bool
    {
        return true;
    }

    public function fetch(?string $sourceRef = null): iterable
    {
        if ($sourceRef === null) {
            throw new InvalidArgumentException(__('integrasi.import.file_missing'));
        }

        $disk = Storage::disk((string) config('semangat.imports.disk'));
        if (! $disk->exists($sourceRef)) {
            throw new InvalidArgumentException(__('integrasi.import.file_missing'));
        }

        $reader = $this->readerFor($sourceRef);
        $reader->open($disk->path($sourceRef));

        try {
            $headers = null;
            $rowNumber = 0;
            foreach ($reader->getSheetIterator() as $sheet) {
                foreach ($sheet->getRowIterator() as $row) {
                    $rowNumber++;
                    $cells = $row->toArray();
                    if ($headers === null) {
                        $headers = array_map(fn ($h) => mb_strtolower(trim((string) $h)), $cells);

                        continue;
                    }
                    if (array_filter($cells, fn ($c) => $c !== null && $c !== '') === []) {
                        continue;
                    }
                    $assoc = [];
                    foreach ($headers as $i => $header) {
                        $assoc[$header] = $cells[$i] ?? null;
                    }

                    yield SourceRowMapper::map($rowNumber, $assoc);
                }

                break; // hanya sheet pertama
            }
        } finally {
            $reader->close();
        }
    }

    private function readerFor(string $path): CsvReader|XlsxReader
    {
        return match (strtolower(pathinfo($path, PATHINFO_EXTENSION))) {
            'xlsx' => new XlsxReader,
            'csv' => new CsvReader(new CsvOptions),
            default => throw new InvalidArgumentException(__('integrasi.import.unsupported')),
        };
    }
}
