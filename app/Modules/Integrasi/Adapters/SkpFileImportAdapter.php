<?php

namespace App\Modules\Integrasi\Adapters;

use App\Modules\Integrasi\Contracts\SkpSource;
use App\Modules\Integrasi\Data\SkpRecord;
use DateTimeInterface;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use OpenSpout\Reader\CSV\Options as CsvOptions;
use OpenSpout\Reader\CSV\Reader as CsvReader;
use OpenSpout\Reader\XLSX\Reader as XlsxReader;

/**
 * INT-02/03: impor berkas SKP terkendali (XLSX/CSV) — dipakai sampai API e-Kinerja pasti (Q-02).
 * Satu baris = satu RHK. Kolom lain (mis. nilai/predikat) DIABAIKAN, tidak pernah dibaca.
 */
class SkpFileImportAdapter implements SkpSource
{
    public const NAME = 'skp_file';

    /** @var list<string> */
    public const COLUMNS = ['nip', 'kode_rhk', 'rencana_hasil_kerja', 'ekspektasi_khusus', 'realisasi', 'catatan_dialog', 'realisasi_akhir'];

    public function name(): string
    {
        return self::NAME;
    }

    public function fetchWorkResults(int $periodYear, ?string $sourceRef = null): iterable
    {
        $disk = Storage::disk((string) config('semangat.imports.disk'));
        if ($sourceRef === null || ! $disk->exists($sourceRef)) {
            throw new InvalidArgumentException(__('integrasi.import.file_missing'));
        }

        $reader = match (strtolower(pathinfo($sourceRef, PATHINFO_EXTENSION))) {
            'xlsx' => new XlsxReader,
            'csv' => new CsvReader(new CsvOptions),
            default => throw new InvalidArgumentException(__('integrasi.import.unsupported')),
        };
        $reader->open($disk->path($sourceRef));

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
                    $get = fn (string $column) => ($i = array_search($column, $headers, true)) === false ? null : ($cells[$i] ?? null);

                    yield new SkpRecord(
                        rowNumber: $number,
                        nip: $get('nip'),
                        periodYear: $periodYear,
                        workResultCode: $get('kode_rhk'),
                        description: $get('rencana_hasil_kerja'),
                        specialExpectation: $get('ekspektasi_khusus'),
                        realization: $get('realisasi'),
                        dialogueNotes: $get('catatan_dialog'),
                        realizationNotes: $get('realisasi_akhir'),
                    );
                }

                break; // hanya sheet pertama
            }
        } finally {
            $reader->close();
        }
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
