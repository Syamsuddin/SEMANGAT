<?php

namespace App\Modules\Integrasi\Actions;

use App\Modules\Integrasi\Data\SkpRecord;
use App\Modules\Integrasi\Enums\SyncStatus;
use App\Modules\Integrasi\Models\Employee;
use App\Modules\Integrasi\Models\SkpSummary;
use App\Modules\Integrasi\Models\SkpWorkResult;
use App\Modules\Integrasi\Models\SyncFailure;
use App\Modules\Integrasi\Models\SyncRun;
use App\Modules\Integrasi\Services\AdapterRegistry;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * INT-02: tarik SKP (RHK, ekspektasi khusus, catatan dialog, realisasi) ke salinan baca-saja hr_skp_*.
 * Dicatat di int_sync_runs (INT-08). Satu baris tidak valid → seluruh berkas ditolak; impor ulang idempoten.
 * Arus satu arah: tidak ada apa pun yang dikirim ke sumber/e-Kinerja.
 */
class RunSkpImport
{
    public function __construct(private readonly AdapterRegistry $registry) {}

    public function __invoke(string $adapter, int $periodYear, ?string $sourceRef = null, ?int $triggeredBy = null): SyncRun
    {
        $run = SyncRun::query()->create([
            'adapter' => $adapter,
            'source_ref' => $sourceRef,
            'started_at' => now(),
            'status' => SyncStatus::Running,
            'triggered_by' => $triggeredBy,
        ]);

        try {
            $source = $this->registry->skpSource($adapter);
            $records = iterator_to_array($source->fetchWorkResults($periodYear, $sourceRef), false);
            if ($records === []) {
                return $this->finish($run, SyncStatus::Failed, 0, [], __('integrasi.import.empty'));
            }

            $employees = Employee::query()
                ->whereIn('nip', array_values(array_unique(array_filter(array_map(fn (SkpRecord $r) => $r->nip, $records)))))
                ->pluck('id', 'nip')->all();
            $errors = $this->validate($records, $employees);
            if ($errors !== []) {
                return $this->finish($run, SyncStatus::Failed, 0, $errors, __('integrasi.import.rejected', ['count' => count($errors)]));
            }

            $written = DB::transaction(fn () => $this->upsert($source->name(), $periodYear, $records, $employees));

            return $this->finish($run, SyncStatus::Success, $written, []);
        } catch (Throwable $e) {
            $this->finish($run, SyncStatus::Failed, 0, [], mb_substr($e->getMessage(), 0, 1000));

            throw $e;
        }
    }

    /**
     * @param  list<SkpRecord>  $records
     * @param  array<string, int>  $employees
     * @return list<array{record: SkpRecord, messages: list<string>}>
     */
    private function validate(array $records, array $employees): array
    {
        $errors = [];
        $seen = [];
        foreach ($records as $record) {
            $messages = [];
            if ($record->nip === null) {
                $messages[] = __('integrasi.validation.nip_required');
            } elseif (preg_match('/^\d{18}$/', $record->nip) !== 1) {
                $messages[] = __('integrasi.validation.nip_format');
            } elseif (! isset($employees[$record->nip])) {
                $messages[] = __('integrasi.skp.employee_unknown');
            }
            if ($record->workResultCode === null) {
                $messages[] = __('integrasi.skp.code_required');
            } elseif (mb_strlen($record->workResultCode) > 40) {
                $messages[] = __('integrasi.validation.too_long', ['field' => 'kode_rhk', 'max' => 40]);
            }
            if ($record->description === null) {
                $messages[] = __('integrasi.skp.description_required');
            }
            $key = $record->nip.'|'.$record->workResultCode;
            if (isset($seen[$key])) {
                $messages[] = __('integrasi.skp.duplicate', ['row' => $seen[$key]]);
            }
            $seen[$key] ??= $record->rowNumber;
            if ($messages !== []) {
                $errors[] = ['record' => $record, 'messages' => $messages];
            }
        }

        return $errors;
    }

    /**
     * @param  list<SkpRecord>  $records
     * @param  array<string, int>  $employees
     */
    private function upsert(string $source, int $periodYear, array $records, array $employees): int
    {
        $now = now();
        $byNip = [];
        foreach ($records as $record) {
            $byNip[(string) $record->nip][] = $record;
        }

        foreach ($byNip as $nip => $rows) {
            $summary = SkpSummary::query()->updateOrCreate(
                ['source' => $source, 'external_id' => "{$nip}-{$periodYear}"],
                [
                    'employee_id' => $employees[$nip],
                    'period_year' => $periodYear,
                    'dialogue_notes' => $this->first($rows, 'dialogueNotes'),
                    'realization_notes' => $this->first($rows, 'realizationNotes'),
                    'synced_at' => $now,
                ],
            );
            foreach ($rows as $row) {
                SkpWorkResult::query()->updateOrCreate(
                    ['source' => $source, 'external_id' => "{$nip}-{$periodYear}-{$row->workResultCode}"],
                    [
                        'skp_summary_id' => $summary->id,
                        'code' => $row->workResultCode,
                        'description' => $row->description,
                        'special_expectation' => $row->specialExpectation,
                        'realization' => $row->realization,
                        'synced_at' => $now,
                    ],
                );
            }
        }

        return count($records);
    }

    /** @param list<SkpRecord> $rows */
    private function first(array $rows, string $property): ?string
    {
        foreach ($rows as $row) {
            if ($row->{$property} !== null) {
                return $row->{$property};
            }
        }

        return null;
    }

    /** @param list<array{record: SkpRecord, messages: list<string>}> $errors */
    private function finish(SyncRun $run, SyncStatus $status, int $ok, array $errors, ?string $error = null): SyncRun
    {
        foreach ($errors as $rowError) {
            SyncFailure::query()->create([
                'sync_run_id' => $run->id,
                'row_number' => $rowError['record']->rowNumber,
                'external_id' => $rowError['record']->nip === null ? null : mb_substr($rowError['record']->nip, 0, 64),
                'payload' => $rowError['record']->toArray(),
                'error' => implode('; ', $rowError['messages']),
            ]);
        }

        $run->forceFill([
            'status' => $status,
            'finished_at' => now(),
            'rows_ok' => $ok,
            'rows_failed' => count($errors),
            'error' => $error,
        ])->save();

        return $run;
    }
}
