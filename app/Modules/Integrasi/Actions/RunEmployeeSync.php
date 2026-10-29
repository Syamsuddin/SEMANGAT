<?php

namespace App\Modules\Integrasi\Actions;

use App\Modules\Integrasi\Data\EmployeeRecord;
use App\Modules\Integrasi\Data\RowError;
use App\Modules\Integrasi\Enums\SyncStatus;
use App\Modules\Integrasi\Events\EmployeesSynced;
use App\Modules\Integrasi\Models\Employee;
use App\Modules\Integrasi\Models\SyncFailure;
use App\Modules\Integrasi\Models\SyncRun;
use App\Modules\Integrasi\Services\AdapterRegistry;
use App\Modules\Integrasi\Services\EmployeeRecordValidator;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * INT-01/03/08: satu run sinkron pegawai, dicatat di int_sync_runs.
 * Berkas: satu baris tidak valid → seluruh berkas ditolak (tidak parsial). API: baris valid ditulis, sisanya antrean ulang.
 */
class RunEmployeeSync
{
    public function __construct(
        private readonly AdapterRegistry $registry,
        private readonly EmployeeRecordValidator $validator,
        private readonly UpsertEmployeeRecords $upsert,
    ) {}

    public function __invoke(string $adapter, ?string $sourceRef = null, ?int $triggeredBy = null): SyncRun
    {
        $run = SyncRun::query()->create([
            'adapter' => $adapter,
            'source_ref' => $sourceRef,
            'started_at' => now(),
            'status' => SyncStatus::Running,
            'triggered_by' => $triggeredBy,
        ]);

        try {
            $source = $this->registry->employeeSource($adapter);
            $records = iterator_to_array($source->fetch($sourceRef), false);

            if ($records === []) {
                return $this->finish($run, SyncStatus::Failed, 0, [], __('integrasi.import.empty'));
            }

            $result = $this->validator->validate($records, $this->knownSupervisorNips($records));

            if ($result->hasErrors() && $source->rejectsWholeBatchOnError()) {
                return $this->finish($run, SyncStatus::Failed, 0, $result->errors,
                    __('integrasi.import.rejected', ['count' => count($result->errors)]));
            }

            $written = DB::transaction(function () use ($source, $result, $run) {
                $written = ($this->upsert)($source->name(), $result->valid, $source->isFullSnapshot() && ! $result->hasErrors(), now());
                EmployeesSynced::dispatch($run);

                return $written;
            });

            return $this->finish($run, $result->hasErrors() ? SyncStatus::Partial : SyncStatus::Success, $written, $result->errors);
        } catch (Throwable $e) {
            $this->finish($run, SyncStatus::Failed, 0, [], mb_substr($e->getMessage(), 0, 1000));

            throw $e;
        }
    }

    /**
     * @param  list<EmployeeRecord>  $records
     * @return array<string, true>
     */
    private function knownSupervisorNips(array $records): array
    {
        $nips = array_values(array_unique(array_filter(array_map(fn (EmployeeRecord $r) => $r->supervisorNip, $records))));

        return array_fill_keys(Employee::query()->whereIn('nip', $nips)->pluck('nip')->all(), true);
    }

    /** @param list<RowError> $errors */
    private function finish(SyncRun $run, SyncStatus $status, int $ok, array $errors, ?string $error = null): SyncRun
    {
        foreach ($errors as $rowError) {
            SyncFailure::query()->create([
                'sync_run_id' => $run->id,
                'row_number' => $rowError->record->rowNumber,
                'external_id' => $rowError->record->nip === null ? null : mb_substr($rowError->record->nip, 0, 64),
                'payload' => $rowError->record->toArray(),
                'error' => $rowError->message(),
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
