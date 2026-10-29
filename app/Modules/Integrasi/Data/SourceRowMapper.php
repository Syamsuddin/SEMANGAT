<?php

namespace App\Modules\Integrasi\Data;

/**
 * Pemetaan kolom templat (berkas & API berbentuk sama, [ASUMSI] Q-06) → EmployeeRecord.
 * Nilai berbahasa Indonesia dipetakan ke kode internal; nilai tak dikenal diteruskan apa adanya
 * agar validator yang menolaknya.
 */
final class SourceRowMapper
{
    /** Kolom templat impor pegawai, urut sesuai berkas contoh. */
    public const COLUMNS = [
        'nip', 'nama', 'jenis_pegawai', 'status', 'kode_opd', 'nama_opd', 'kode_unit', 'nama_unit',
        'kode_jabatan', 'nama_jabatan', 'jenis_jabatan', 'jenjang_jabatan', 'kelas_jabatan',
        'rumpun_jabatan', 'nip_atasan', 'email', 'telepon',
    ];

    private const STATUS = ['aktif' => 'active', 'nonaktif' => 'inactive', 'active' => 'active', 'inactive' => 'inactive'];

    private const POSITION_TYPE = [
        'struktural' => 'structural', 'fungsional' => 'functional', 'pelaksana' => 'executor',
        'structural' => 'structural', 'functional' => 'functional', 'executor' => 'executor',
    ];

    /** @param array<string, mixed> $row kunci = nama kolom templat */
    public static function map(int $rowNumber, array $row): EmployeeRecord
    {
        $rawError = null;
        $get = function (string $key) use ($row, &$rawError): ?string {
            $value = $row[$key] ?? null;
            if ($value === null) {
                return null;
            }
            if (in_array($key, ['nip', 'nip_atasan'], true) && ! is_string($value)) {
                $rawError = __('integrasi.import.nip_not_text');

                return null;
            }
            if (! is_scalar($value)) {
                return null;
            }
            $value = trim((string) $value);

            return $value === '' ? null : $value;
        };

        $lower = fn (?string $v): ?string => $v === null ? null : mb_strtolower($v);

        $status = $lower($get('status'));
        $positionType = $lower($get('jenis_jabatan'));

        return new EmployeeRecord(
            rowNumber: $rowNumber,
            nip: $get('nip'),
            name: $get('nama'),
            employmentType: $lower($get('jenis_pegawai')),
            status: $status === null ? null : (self::STATUS[$status] ?? $status),
            opdCode: $get('kode_opd'),
            opdName: $get('nama_opd'),
            unitCode: $get('kode_unit'),
            unitName: $get('nama_unit'),
            positionCode: $get('kode_jabatan'),
            positionName: $get('nama_jabatan'),
            positionType: $positionType === null ? null : (self::POSITION_TYPE[$positionType] ?? $positionType),
            positionTier: $lower($get('jenjang_jabatan')),
            gradeClass: $get('kelas_jabatan'),
            positionFamily: $get('rumpun_jabatan'),
            supervisorNip: $get('nip_atasan'),
            email: $get('email'),
            phone: $get('telepon'),
            rawError: $rawError,
        );
    }
}
