<?php

use App\Modules\Fondasi\Actions\AssignRole;
use App\Modules\Fondasi\Actions\SyncUserRoles;
use App\Modules\Fondasi\Enums\Role;
use App\Modules\Fondasi\Models\User;
use App\Modules\Integrasi\Actions\RunEmployeeSync;
use Illuminate\Validation\ValidationException;

const NIP_A = '199901012010011201';
const NIP_B = '199901012015011202';
const NIP_C = '199901012010011203';

it('FON-03 sinkron menetapkan A sebagai atasan B → A berperan atasan dan melihat B di "Tim saya"; B pindah atasan → A kehilangan akses', function () {
    $sync = app(RunEmployeeSync::class);
    $sync('employee_file', importRows([
        employeeRow(NIP_A), employeeRow(NIP_C), employeeRow(NIP_B, ['nip_atasan' => NIP_A]),
    ]));

    $a = User::query()->where('username', NIP_A)->firstOrFail();
    $b = User::query()->where('username', NIP_B)->firstOrFail();

    expect($a->hasRole(Role::Atasan->value))->toBeTrue()
        ->and($b->hasRole(Role::Atasan->value))->toBeFalse()
        ->and($b->hasRole(Role::Asn->value))->toBeTrue();

    $this->actingAs($a)->get(route('fondasi.team.index'))->assertOk()->assertSee('Pegawai '.NIP_B);
    $this->actingAs($a)->get(route('fondasi.employees.show', $b->employee_id))->assertOk();

    $sync('employee_file', importRows([employeeRow(NIP_B, ['nip_atasan' => NIP_C])]));

    expect($a->refresh()->hasRole(Role::Atasan->value))->toBeFalse()
        ->and(User::query()->where('username', NIP_C)->firstOrFail()->hasRole(Role::Atasan->value))->toBeTrue();

    $this->actingAs($a->refresh())->get(route('fondasi.employees.show', $b->employee_id))->assertNotFound();
    $this->actingAs($a)->get(route('fondasi.team.index'))->assertForbidden();
})->group('fondasi');

it('FON-03 sinkron membuat akun bagi pegawai baru dan menonaktifkan akun pegawai inactive', function () {
    $sync = app(RunEmployeeSync::class);
    $sync('employee_file', importRows([employeeRow(NIP_A)]));
    expect(User::query()->where('username', NIP_A)->value('is_active'))->toBeTrue();

    $sync('employee_file', importRows([employeeRow(NIP_A, ['status' => 'nonaktif'])]));
    $user = User::query()->where('username', NIP_A)->firstOrFail();

    expect($user->is_active)->toBeFalse()->and($user->roles)->toBeEmpty();
})->group('fondasi');

it('FON-03 peran turunan tidak dapat ditugaskan manual', function (Role $role) {
    expect(fn () => app(AssignRole::class)(employeeUser(), $role, 'coba'))->toThrow(ValidationException::class);
})->with([Role::Asn, Role::Atasan])->group('fondasi');

it('FON-02 penugasan koordinator OPD wajib cakupan OPD dan tercatat di audit', function () {
    $user = employeeUser();

    expect(fn () => app(AssignRole::class)($user, Role::KoordinatorOpd, 'SK koordinator'))->toThrow(ValidationException::class);

    app(AssignRole::class)($user, Role::KoordinatorOpd, 'SK koordinator', opdId: $user->employee->opd_id);

    expect($user->refresh()->hasRole(Role::KoordinatorOpd->value))->toBeTrue();
    $this->assertDatabaseHas('sys_audit_logs', ['action' => 'role_assign', 'reason' => 'SK koordinator']);
})->group('fondasi');

it('FON-02 penugasan kedaluwarsa tidak lagi memberi peran', function () {
    $user = employeeUser();
    app(AssignRole::class)($user, Role::Auditor, 'Audit triwulan', endsAt: now()->addDay());
    expect($user->refresh()->hasRole(Role::Auditor->value))->toBeTrue();

    $this->travel(2)->days();
    app(SyncUserRoles::class)($user);

    expect($user->refresh()->hasRole(Role::Auditor->value))->toBeFalse();
})->group('fondasi');
