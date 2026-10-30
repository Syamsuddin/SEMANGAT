<?php

use App\Modules\Fondasi\Enums\Role;
use App\Modules\Integrasi\Models\Employee;
use App\Modules\Integrasi\Models\OrgUnit;
use App\Modules\Kompetensi\Livewire\TeamGapSummary;
use App\Modules\Kompetensi\Models\Assessment;
use App\Modules\Kompetensi\Models\Gap;
use Livewire\Livewire;

/*
| Matriks docs/05 untuk M03 (F2), 9 peran. Pengguna uji di OPD A; "rekan" = pegawai lain OPD A (bukan bawahan),
| "luar" = pegawai OPD B. Profil ringkas: S/T/O/B; clo & dewan agregat saja (A). Laporan rinci: S + pengelola K* + auditor B* (beralasan).
*/

beforeEach(function () {
    $this->opdA = OrgUnit::factory()->create();
    $this->opdB = OrgUnit::factory()->create();
});

it('KOM-04 halaman M03 per peran sesuai matriks cakupan', function (Role $role, int $peer, int $outside, int $team, int $validation, int $aggregate, int $import, int $detail) {
    $user = userWithRole($role, $this->opdA);
    $colleague = Employee::factory()->inOpd($this->opdA)->create();
    $stranger = Employee::factory()->inOpd($this->opdB)->create();
    $peerAssessment = Assessment::factory()->create(['employee_id' => $colleague->id]);

    $this->actingAs($user)->get(route('kompetensi.profile.show'))->assertOk();
    $this->actingAs($user)->get(route('kompetensi.self.create'))->assertOk();
    $this->actingAs($user)->get(route('kompetensi.profile.employee', $colleague))->assertStatus($peer);
    $this->actingAs($user)->get(route('kompetensi.profile.employee', $stranger))->assertStatus($outside);
    $this->actingAs($user)->get(route('kompetensi.team.index'))->assertStatus($team);
    $this->actingAs($user)->get(route('kompetensi.validation.index'))->assertStatus($validation);
    $this->actingAs($user)->get(route('kompetensi.aggregate.index'))->assertStatus($aggregate);
    $this->actingAs($user)->get(route('kompetensi.import'))->assertStatus($import);
    $this->actingAs($user)->get(route('kompetensi.assessments.show', $peerAssessment))->assertStatus($detail);
})->with([
    // peran, profil rekan, profil luar, tim, validasi, agregat, impor, rincian asesmen rekan
    'asn' => [Role::Asn, 404, 404, 403, 403, 403, 403, 404],
    'atasan' => [Role::Atasan, 404, 404, 200, 200, 403, 403, 404],
    'koordinator_opd' => [Role::KoordinatorOpd, 200, 404, 403, 403, 200, 403, 403],
    'fasilitator' => [Role::Fasilitator, 404, 404, 403, 403, 403, 403, 404],
    'pengelola' => [Role::Pengelola, 200, 200, 403, 403, 200, 200, 200],
    'clo' => [Role::Clo, 404, 404, 403, 403, 200, 403, 404],
    'dewan_pengarah' => [Role::DewanPengarah, 404, 404, 403, 403, 200, 403, 404],
    'admin_teknis' => [Role::AdminTeknis, 404, 404, 403, 403, 403, 403, 404],
    'auditor' => [Role::Auditor, 200, 200, 403, 403, 200, 403, 200],
])->group('scope');

it('Akses F2 atasan melihat profil ringkas bawahan langsung tetapi bukan laporan rinci asesmennya', function () {
    $atasan = userWithRole(Role::Atasan, $this->opdA);
    $sub = Employee::query()->where('supervisor_id', $atasan->employee_id)->firstOrFail();
    $assessment = Assessment::factory()->create(['employee_id' => $sub->id, 'notes' => 'catatan rahasia psikogram']);

    $this->actingAs($atasan)->get(route('kompetensi.profile.employee', $sub))
        ->assertOk()->assertDontSee('catatan rahasia psikogram')
        ->assertDontSee(route('kompetensi.assessments.show', $assessment));
    $this->actingAs($atasan)->get(route('kompetensi.assessments.show', $assessment))->assertForbidden();
})->group('scope');

it('Akses F2 ringkasan gap tim hanya memuat bawahan langsung', function () {
    $atasan = userWithRole(Role::Atasan, $this->opdA);
    $sub = Employee::query()->where('supervisor_id', $atasan->employee_id)->firstOrFail();
    $other = Employee::factory()->inOpd($this->opdA)->create(['name' => 'Pegawai Bukan Bawahan']);
    Gap::factory()->create(['employee_id' => $sub->id]);
    Gap::factory()->create(['employee_id' => $other->id]);

    Livewire::actingAs($atasan)->test(TeamGapSummary::class)
        ->assertSee($sub->name)->assertDontSee('Pegawai Bukan Bawahan');
})->group('scope');

it('KOM-04 tamu dialihkan ke halaman masuk', function () {
    $this->get(route('kompetensi.profile.show'))->assertRedirect(route('login'));
    $this->get(route('kompetensi.aggregate.index'))->assertRedirect(route('login'));
})->group('scope');
