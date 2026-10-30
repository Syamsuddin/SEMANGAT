<?php

use App\Modules\Fondasi\Actions\AssignRole;
use App\Modules\Fondasi\Actions\SyncUserRoles;
use App\Modules\Fondasi\Enums\Role;
use App\Modules\Fondasi\Models\User;
use App\Modules\Integrasi\Data\SourceRowMapper;
use App\Modules\Integrasi\Models\Employee;
use App\Modules\Integrasi\Models\OrgUnit;
use App\Modules\Integrasi\Models\Position;
use App\Modules\Kompetensi\Models\JobStandard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

pest()->extend(TestCase::class)->use(RefreshDatabase::class)->in('Feature', 'Scope', 'Contract', 'Unit');

/*
| Bantuan: akun pegawai dengan peran tertentu. Peran selalu lewat jalur resmi
| (AssignRole untuk peran penugasan, data supervisor untuk atasan) — tidak pernah assignRole() langsung.
*/
function employeeUser(?Employee $employee = null, array $userAttributes = []): User
{
    $employee ??= Employee::factory()->create();
    $user = User::factory()->forEmployee($employee)->create($userAttributes);
    app(SyncUserRoles::class)($user);

    return $user->refresh();
}

function userWithRole(Role $role, ?OrgUnit $opd = null, bool $twoFactor = true): User
{
    $employee = Employee::factory()->when($opd, fn ($f) => $f->inOpd($opd))->create();
    $user = employeeUser($employee, $twoFactor && $role->requiresTwoFactor() ? confirmedTwoFactor() : []);

    if ($role === Role::Atasan) {
        Employee::factory()->supervisedBy($employee)->create();
        app(SyncUserRoles::class)($user);
    } elseif ($role !== Role::Asn) {
        app(AssignRole::class)($user, $role, 'Penugasan uji', opdId: $role->requiresOpdScope() ? ($opd ?? $employee->opd)->id : null);
    }

    return $user->refresh();
}

/** @return array<string, mixed> */
function confirmedTwoFactor(): array
{
    return [
        'two_factor_secret' => encrypt('JBSWY3DPEHPK3PXP'),
        'two_factor_recovery_codes' => encrypt(json_encode(['kode-1', 'kode-2'])),
        'two_factor_confirmed_at' => now(),
    ];
}

/** Salin fixture berkas ke disk impor dan kembalikan source_ref-nya. */
function importFixture(string $name): string
{
    $ref = 'imports/'.Str::uuid().'.'.pathinfo($name, PATHINFO_EXTENSION);
    Storage::disk(config('semangat.imports.disk'))
        ->put($ref, (string) file_get_contents(base_path('tests/Fixtures/employee_file/'.$name)));

    return $ref;
}

/** Tulis CSV templat dari baris asosiatif → source_ref. */
function importRows(array $rows): string
{
    $columns = SourceRowMapper::COLUMNS;
    $lines = [implode(',', $columns)];
    foreach ($rows as $row) {
        $lines[] = implode(',', array_map(fn ($c) => $row[$c] ?? '', $columns));
    }
    $ref = 'imports/'.Str::uuid().'.csv';
    Storage::disk(config('semangat.imports.disk'))->put($ref, implode("\n", $lines)."\n");

    return $ref;
}

function employeeRow(string $nip, array $overrides = []): array
{
    return $overrides + [
        'nip' => $nip, 'nama' => 'Pegawai '.$nip, 'jenis_pegawai' => 'pns', 'status' => 'aktif',
        'kode_opd' => 'OPD-01', 'nama_opd' => 'Badan Kepegawaian',
    ];
}

/** Tulis CSV ke disk impor (kolom judul + baris asosiatif) → source_ref. Dipakai impor kompetensi & SKP (F2). */
function csvImport(array $columns, array $rows): string
{
    $escape = fn ($v) => preg_match('/[",\n]/', (string) $v) ? '"'.str_replace('"', '""', (string) $v).'"' : (string) $v;
    $lines = [implode(',', $columns)];
    foreach ($rows as $row) {
        $lines[] = implode(',', array_map(fn ($c) => $escape($row[$c] ?? ''), $columns));
    }
    $ref = 'imports/'.Str::uuid().'.csv';
    Storage::disk(config('semangat.imports.disk'))->put($ref, implode("\n", $lines)."\n");

    return $ref;
}

/**
 * Pegawai dengan jabatan ber-SKJ. $levels: competency_id => level dipersyaratkan; kosong = 3 kompetensi baru level 3.
 *
 * @return array{0: Employee, 1: JobStandard}
 */
function employeeWithSkj(array $levels = [], ?Employee $supervisor = null, ?OrgUnit $opd = null): array
{
    $position = Position::factory()->create();
    $standard = JobStandard::factory()->forPosition($position)
        ->when($levels === [], fn ($f) => $f->withCompetencies(3), fn ($f) => $f->requiring($levels))
        ->create();
    $employee = Employee::factory()
        ->when($supervisor, fn ($f) => $f->supervisedBy($supervisor))
        ->when($opd && ! $supervisor, fn ($f) => $f->inOpd($opd))
        ->create(['position_id' => $position->id]);

    return [$employee->load('position'), $standard->load('items')];
}

/** Salin fixture berkas SKP ke disk impor → source_ref (INT-02). */
function skpFixture(string $name): string
{
    $ref = 'imports/'.Str::uuid().'.csv';
    Storage::disk(config('semangat.imports.disk'))->put($ref, (string) file_get_contents(base_path('tests/Fixtures/skp_file/'.$name)));

    return $ref;
}
