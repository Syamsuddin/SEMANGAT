<?php

use App\Modules\Fondasi\Enums\Role;
use App\Modules\Fondasi\Livewire\EmployeeIndex;
use App\Modules\Integrasi\Models\Employee;
use App\Modules\Integrasi\Models\OrgUnit;
use Livewire\Livewire;

/*
| FON-04 matriks docs/05 untuk data pegawai individu:
| asn S · atasan T · koordinator_opd O · pengelola K · auditor B · fasilitator/clo/dewan/admin_teknis: hanya diri sendiri.
| Setiap pengguna uji ada di OPD A. "Rekan" = pegawai lain di OPD A (bukan bawahan); "Luar" = pegawai OPD B.
*/

beforeEach(function () {
    $this->opdA = OrgUnit::factory()->create();
    $this->opdB = OrgUnit::factory()->create();
});

$matrix = [
    // peran, lihat rekan OPD A, lihat pegawai OPD B, buka daftar pegawai
    'asn' => [Role::Asn, 404, 404, 403],
    'atasan' => [Role::Atasan, 404, 404, 200],
    'koordinator_opd' => [Role::KoordinatorOpd, 200, 404, 200],
    'fasilitator' => [Role::Fasilitator, 404, 404, 403],
    'pengelola' => [Role::Pengelola, 200, 200, 200],
    'clo' => [Role::Clo, 404, 404, 403],
    'dewan_pengarah' => [Role::DewanPengarah, 404, 404, 403],
    'admin_teknis' => [Role::AdminTeknis, 404, 404, 403],
    'auditor' => [Role::Auditor, 200, 200, 200],
];

it('FON-04 /pegawai/{id} pegawai lain sesuai matriks cakupan', function (Role $role, int $peer, int $outside, int $index) {
    $user = userWithRole($role, $this->opdA);
    $colleague = Employee::factory()->inOpd($this->opdA)->create();
    $stranger = Employee::factory()->inOpd($this->opdB)->create();

    $this->actingAs($user)->get(route('fondasi.employees.show', $colleague))->assertStatus($peer);
    $this->actingAs($user)->get(route('fondasi.employees.show', $stranger))->assertStatus($outside);
    $this->actingAs($user)->get(route('fondasi.employees.show', $user->employee_id))->assertOk();
    $this->actingAs($user)->get(route('fondasi.employees.index'))->assertStatus($index);
})->with($matrix)->group('scope');

it('FON-04 daftar pegawai tidak memuat baris di luar cakupan', function (Role $role, int $peer, int $outside, int $index) {
    if ($index !== 200) {
        expect(true)->toBeTrue();

        return;
    }
    $user = userWithRole($role, $this->opdA);
    $colleague = Employee::factory()->inOpd($this->opdA)->create(['name' => 'Rekan Satu OPD']);
    $stranger = Employee::factory()->inOpd($this->opdB)->create(['name' => 'Pegawai OPD Lain']);

    $names = Livewire::actingAs($user)->test(EmployeeIndex::class)->instance()->employees->pluck('name');

    expect($names->contains('Rekan Satu OPD'))->toBe($peer === 200)
        ->and($names->contains('Pegawai OPD Lain'))->toBe($outside === 200);
})->with($matrix)->group('scope');

it('FON-04 atasan melihat bawahan langsung, bukan bawahan dari bawahannya', function () {
    $atasan = userWithRole(Role::Atasan, $this->opdA);
    $direct = Employee::query()->where('supervisor_id', $atasan->employee_id)->firstOrFail();
    $indirect = Employee::factory()->supervisedBy($direct)->create();

    $this->actingAs($atasan)->get(route('fondasi.employees.show', $direct))->assertOk();
    $this->actingAs($atasan)->get(route('fondasi.employees.show', $indirect))->assertNotFound();
})->group('scope');

it('FON-04 halaman integrasi sesuai matriks M02', function (Role $role, int $panel, int $import) {
    $user = userWithRole($role, $this->opdA);

    $this->actingAs($user)->get(route('integrasi.panel'))->assertStatus($panel);
    $this->actingAs($user)->get(route('integrasi.import'))->assertStatus($import);
    $this->actingAs($user)->get(route('integrasi.template'))->assertStatus($import);
})->with([
    'asn' => [Role::Asn, 403, 403],
    'atasan' => [Role::Atasan, 403, 403],
    'koordinator_opd' => [Role::KoordinatorOpd, 403, 403],
    'fasilitator' => [Role::Fasilitator, 403, 403],
    'pengelola' => [Role::Pengelola, 200, 200],
    'clo' => [Role::Clo, 200, 403],
    'dewan_pengarah' => [Role::DewanPengarah, 403, 403],
    'admin_teknis' => [Role::AdminTeknis, 403, 403],
    'auditor' => [Role::Auditor, 200, 403],
])->group('scope');

it('FON-04 "Tim saya" hanya untuk atasan', function (Role $role) {
    $this->actingAs(userWithRole($role, $this->opdA))->get(route('fondasi.team.index'))
        ->assertStatus($role === Role::Atasan ? 200 : 403);
})->with(Role::cases())->group('scope');

it('FON-04 tamu dialihkan ke halaman masuk', function () {
    $employee = Employee::factory()->create();

    $this->get(route('fondasi.employees.show', $employee))->assertRedirect(route('login'));
    $this->get(route('integrasi.panel'))->assertRedirect(route('login'));
})->group('scope');
