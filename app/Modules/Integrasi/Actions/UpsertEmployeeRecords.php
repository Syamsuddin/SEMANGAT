<?php

namespace App\Modules\Integrasi\Actions;

use App\Modules\Integrasi\Data\EmployeeRecord;
use App\Modules\Integrasi\Enums\EmployeeStatus;
use App\Modules\Integrasi\Enums\OrgUnitLevel;
use App\Modules\Integrasi\Models\Employee;
use App\Modules\Integrasi\Models\OrgUnit;
use App\Modules\Integrasi\Models\Position;
use Carbon\CarbonInterface;

/**
 * Upsert idempoten baris valid (docs/06 § 2). Dipanggil di dalam transaksi oleh RunEmployeeSync.
 * Kunci pegawai = NIP (UNIQUE); kunci unit/jabatan = (source, external_id = kode).
 */
class UpsertEmployeeRecords
{
    /** @var array<string, OrgUnit> */
    private array $units = [];

    /** @var array<string, Position> */
    private array $positions = [];

    /**
     * @param  list<EmployeeRecord>  $records
     * @return int jumlah pegawai yang ditulis
     */
    public function __invoke(string $source, array $records, bool $fullSnapshot, CarbonInterface $syncedAt): int
    {
        $this->units = [];
        $this->positions = [];
        $integration = ['source' => $source, 'synced_at' => $syncedAt];

        foreach ($records as $r) {
            $opd = $this->unit($integration, (string) $r->opdCode, (string) $r->opdName, OrgUnitLevel::Opd, null);
            $unit = $r->unitCode === null || $r->unitCode === $r->opdCode
                ? $opd
                : $this->unit($integration, $r->unitCode, (string) $r->unitName, OrgUnitLevel::Division, $opd);
            $position = $r->positionCode === null ? null : $this->position($integration, $r);

            Employee::query()->updateOrCreate(['nip' => $r->nip], $integration + [
                'external_id' => $r->nip,
                'name' => $r->name,
                'employment_type' => $r->employmentType,
                'status' => $r->status,
                'org_unit_id' => $unit->id,
                'opd_id' => $opd->id,
                'position_id' => $position?->id,
                'email' => $r->email,
                'phone' => $r->phone,
            ]);
        }

        $this->linkSupervisors($records);

        if ($fullSnapshot) {
            $nips = array_map(fn (EmployeeRecord $r) => (string) $r->nip, $records);
            Employee::query()
                ->where('source', $source)
                ->whereNotIn('nip', $nips)
                ->where('status', EmployeeStatus::Active->value)
                ->update(['status' => EmployeeStatus::Inactive->value, 'synced_at' => $syncedAt]);
        }

        return count($records);
    }

    /** @param list<EmployeeRecord> $records */
    private function linkSupervisors(array $records): void
    {
        $nips = [];
        foreach ($records as $r) {
            $nips[] = (string) $r->nip;
            if ($r->supervisorNip !== null) {
                $nips[] = $r->supervisorNip;
            }
        }
        $ids = Employee::query()->whereIn('nip', array_unique($nips))->pluck('id', 'nip');

        foreach ($records as $r) {
            Employee::query()->where('nip', $r->nip)->update([
                'supervisor_id' => $r->supervisorNip === null ? null : $ids[$r->supervisorNip],
            ]);
        }
    }

    /** @param array{source: string, synced_at: CarbonInterface} $integration */
    private function unit(array $integration, string $code, string $name, OrgUnitLevel $level, ?OrgUnit $opd): OrgUnit
    {
        $key = $level->value.'|'.$code;
        if (isset($this->units[$key])) {
            return $this->units[$key];
        }

        $unit = OrgUnit::query()->updateOrCreate(
            ['source' => $integration['source'], 'external_id' => $code],
            $integration + ['code' => $code, 'name' => $name, 'level' => $level, 'parent_id' => $opd?->id, 'opd_id' => $opd?->id],
        );

        if ($opd === null && $unit->opd_id !== $unit->id) {
            $unit->forceFill(['opd_id' => $unit->id])->save();
        }

        return $this->units[$key] = $unit;
    }

    /** @param array{source: string, synced_at: CarbonInterface} $integration */
    private function position(array $integration, EmployeeRecord $r): Position
    {
        $code = (string) $r->positionCode;
        if (isset($this->positions[$code])) {
            return $this->positions[$code];
        }

        return $this->positions[$code] = Position::query()->updateOrCreate(
            ['source' => $integration['source'], 'external_id' => $code],
            $integration + [
                'code' => $code,
                'name' => $r->positionName,
                'type' => $r->positionType,
                'tier' => $r->positionTier,
                'grade_class' => $r->gradeClass === null ? null : (int) $r->gradeClass,
                'family' => $r->positionFamily,
            ],
        );
    }
}
