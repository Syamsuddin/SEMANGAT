<?php

namespace App\Modules\Kompetensi\Services;

use DateTimeInterface;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use OpenSpout\Reader\CSV\Options as CsvOptions;
use OpenSpout\Reader\CSV\Reader as CsvReader;
use OpenSpout\Reader\XLSX\Reader as XlsxReader;

/** Membaca berkas templat kompetensi (XLSX/CSV, sheet pertama, baris 1 = judul kolom) dari disk impor. */
class CompetencyFileReader
{
    /**
     * @return array<int, array<string, string|null>> nomor baris berkas => kolom (judul huruf kecil) => nilai teks
     */
    public function rows(string $path): array
    {
        $disk = Storage::disk((string) config('semangat.imports.disk'));
        if (! $disk->exists($path)) {
            throw new InvalidArgumentException(__('kompetensi.import.file_missing'));
        }

        $reader = match (strtolower(pathinfo($path, PATHINFO_EXTENSION))) {
            'xlsx' => new XlsxReader,
            'csv' => new CsvReader(new CsvOptions),
            default => throw new InvalidArgumentException(__('kompetensi.import.unsupported')),
        };
        $reader->open($disk->path($path));

        $rows = [];
        try {
            foreach ($reader->getSheetIterator() as $sheet) {
                $headers = null;
                $number = 0;
                foreach ($sheet->getRowIterator() as $row) {
                    $number++;
                    $cells = array_map(fn ($c) => $this->text($c), $row->toArray());
                    if ($headers === null) {
                        $headers = array_map(fn ($h) => mb_strtolower((string) $h), $cells);

                        continue;
                    }
                    if (array_filter($cells, fn ($c) => $c !== null) === []) {
                        continue;
                    }
                    $assoc = [];
                    foreach ($headers as $i => $header) {
                        $assoc[$header] = $cells[$i] ?? null;
                    }
                    $rows[$number] = $assoc;
                }

                break; // hanya sheet pertama
            }
        } finally {
            $reader->close();
        }

        return $rows;
    }

    private function text(mixed $cell): ?string
    {
        if ($cell instanceof DateTimeInterface) {
            return $cell->format('Y-m-d');
        }
        if (is_float($cell) && floor($cell) === $cell) {
            $cell = (int) $cell;
        }
        $value = trim((string) (is_scalar($cell) ? $cell : ''));

        return $value === '' ? null : $value;
    }
}
