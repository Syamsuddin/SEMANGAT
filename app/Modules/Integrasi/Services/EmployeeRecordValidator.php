<?php

namespace App\Modules\Integrasi\Services;

use App\Modules\Integrasi\Data\EmployeeRecord;
use App\Modules\Integrasi\Data\RowError;
use App\Modules\Integrasi\Data\ValidationResult;
use App\Modules\Integrasi\Enums\EmployeeStatus;
use App\Modules\Integrasi\Enums\EmploymentType;
use App\Modules\Integrasi\Enums\PositionTier;
use App\Modules\Integrasi\Enums\PositionType;

/**
 * Validasi deterministik baris pegawai (INT-01/03). Murni: NIP atasan yang sudah ada di DB disuntikkan.
 */
final class EmployeeRecordValidator
{
    /**
     * @param  list<EmployeeRecord>  $records
     * @param  array<string, true>  $knownNips  NIP yang sudah ada di hr_employees
     */
    public function validate(array $records, array $knownNips = []): ValidationResult
    {
        $nipCounts = array_count_values(array_filter(array_map(fn (EmployeeRecord $r) => $r->nip, $records)));
        $snapshotNips = array_fill_keys(array_keys($nipCounts), true);

        $valid = [];
        $errors = [];

        foreach ($records as $record) {
            $messages = $this->rowMessages($record, $nipCounts, $snapshotNips + $knownNips);
            if ($messages === []) {
                $valid[] = $record;
            } else {
                $errors[] = new RowError($record, $messages);
            }
        }

        return new ValidationResult($valid, $errors);
    }

    /**
     * @param  array<string, int>  $nipCounts
     * @param  array<string, true>  $resolvableNips
     * @return list<string>
     */
    private function rowMessages(EmployeeRecord $r, array $nipCounts, array $resolvableNips): array
    {
        $m = [];
        $t = fn (string $key, array $replace = []) => __('integrasi.validation.'.$key, $replace);

        if ($r->rawError !== null) {
            $m[] = $r->rawError;
        }

        if ($r->nip === null) {
            $m[] = $t('nip_required');
        } elseif (! preg_match('/^\d{18}$/', $r->nip)) {
            $m[] = $t('nip_format');
        } elseif (($nipCounts[$r->nip] ?? 0) > 1) {
            $m[] = $t('nip_duplicate');
        }

        if ($r->name === null) {
            $m[] = $t('name_required');
        } elseif (mb_strlen($r->name) > 150) {
            $m[] = $t('too_long', ['field' => 'nama', 'max' => 150]);
        }

        if (EmploymentType::tryFrom((string) $r->employmentType) === null) {
            $m[] = $t('employment_type');
        }

        if (EmployeeStatus::tryFrom((string) $r->status) === null) {
            $m[] = $t('status');
        }

        if ($r->opdCode === null || $r->opdName === null) {
            $m[] = $t('opd_required');
        }

        if (($r->unitCode === null) !== ($r->unitName === null)) {
            $m[] = $t('unit_pair');
        }

        if ($r->positionCode !== null) {
            if ($r->positionName === null) {
                $m[] = $t('position_name_required');
            }
            if (PositionType::tryFrom((string) $r->positionType) === null) {
                $m[] = $t('position_type');
            }
            if (PositionTier::tryFrom((string) $r->positionTier) === null) {
                $m[] = $t('position_tier');
            }
            if ($r->gradeClass !== null && (! ctype_digit($r->gradeClass) || (int) $r->gradeClass > 255)) {
                $m[] = $t('grade_class');
            }
        }

        if ($r->supervisorNip !== null) {
            if (! preg_match('/^\d{18}$/', $r->supervisorNip)) {
                $m[] = $t('supervisor_format');
            } elseif ($r->supervisorNip === $r->nip) {
                $m[] = $t('supervisor_self');
            } elseif (! isset($resolvableNips[$r->supervisorNip])) {
                $m[] = $t('supervisor_unknown');
            }
        }

        if ($r->email !== null && filter_var($r->email, FILTER_VALIDATE_EMAIL) === false) {
            $m[] = $t('email');
        }

        foreach (['opdCode' => 40, 'unitCode' => 40, 'positionCode' => 40, 'opdName' => 200, 'unitName' => 200, 'positionName' => 200, 'positionFamily' => 100, 'email' => 150, 'phone' => 20] as $field => $max) {
            $value = $r->{$field};
            if (is_string($value) && mb_strlen($value) > $max) {
                $m[] = $t('too_long', ['field' => $field, 'max' => $max]);
            }
        }

        return $m;
    }
}
