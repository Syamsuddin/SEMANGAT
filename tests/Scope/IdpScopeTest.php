<?php

use App\Modules\Fondasi\Enums\Role;
use App\Modules\Idp\Actions\PublishLearningPlan;
use App\Modules\Idp\Enums\IdpStatus;
use App\Modules\Idp\Livewire\TeamApprovalList;
use App\Modules\Idp\Livewire\UnlinkedIdpList;
use App\Modules\Idp\Models\Idp;
use App\Modules\Integrasi\Models\Employee;
use App\Modules\Integrasi\Models\OrgUnit;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

/*
| Matriks docs/05 untuk M06 (F3), 9 peran: asn K(S) · atasan K(T) · koord. O · pengelola B · clo A (agregat, tanpa baris) · auditor B.
| "rekan" = pegawai lain OPD A (bukan bawahan), "luar" = pegawai OPD B. Keduanya punya IDP active bertanda tanpa tautan + Rencana terbit.
*/

beforeEach(function () {
    Storage::fake(config('semangat.documents.disk'));
    $this->opdA = OrgUnit::factory()->create();
    $this->opdB = OrgUnit::factory()->create();
});

/** IDP active ber-Rencana terbit, satu butir tanpa tautan (IDP-10). */
function publishedIdp(Employee $employee): Idp
{
    $idp = idpWithItems($employee, 2, 'approved');
    app(PublishLearningPlan::class)->handle(employeeUser($employee), $idp);
    $idp->update(['unlinked_items_count' => 1]);

    return $idp->refresh();
}

it('IDP-04 halaman & unduhan M06 per peran sesuai matriks cakupan', function (Role $role, int $team, int $unlinked, int $peer, int $outside) {
    $user = userWithRole($role, $this->opdA);
    $peerIdp = publishedIdp(Employee::factory()->inOpd($this->opdA)->create());
    $outsideIdp = publishedIdp(Employee::factory()->inOpd($this->opdB)->create());

    $this->actingAs($user)->get(route('idp.plans.mine'))->assertOk();
    $this->actingAs($user)->get(route('idp.team.index'))->assertStatus($team);
    $this->actingAs($user)->get(route('idp.unlinked.index'))->assertStatus($unlinked);
    $this->actingAs($user)->get(route('idp.plans.download', $peerIdp))->assertStatus($peer);
    $this->actingAs($user)->get(route('idp.plans.download', $outsideIdp))->assertStatus($outside);
})->with([
    // peran, IDP tim, tanpa tautan, unduh Rencana rekan OPD, unduh Rencana luar OPD
    'asn' => [Role::Asn, 403, 403, 403, 403],
    'atasan' => [Role::Atasan, 200, 200, 403, 403],
    'koordinator_opd' => [Role::KoordinatorOpd, 403, 200, 200, 403],
    'fasilitator' => [Role::Fasilitator, 403, 403, 403, 403],
    'pengelola' => [Role::Pengelola, 403, 200, 200, 200],
    'clo' => [Role::Clo, 403, 403, 403, 403],
    'dewan_pengarah' => [Role::DewanPengarah, 403, 403, 403, 403],
    'admin_teknis' => [Role::AdminTeknis, 403, 403, 403, 403],
    'auditor' => [Role::Auditor, 403, 200, 200, 200],
])->group('scope');

it('IDP-10 daftar tanpa tautan hanya memuat baris dalam cakupan', function (Role $role, bool $seesSub, bool $seesPeer, bool $seesOutside) {
    $user = userWithRole($role, $this->opdA);
    $sub = Employee::factory()->inOpd($this->opdA)->supervisedBy($user->employee)->create(['name' => 'Bawahan Langsung']);
    publishedIdp($sub);
    publishedIdp(Employee::factory()->inOpd($this->opdA)->create(['name' => 'Rekan Satu OPD']));
    publishedIdp(Employee::factory()->inOpd($this->opdB)->create(['name' => 'Pegawai OPD Lain']));

    $page = Livewire::actingAs($user->refresh())->test(UnlinkedIdpList::class);
    foreach (['Bawahan Langsung' => $seesSub, 'Rekan Satu OPD' => $seesPeer, 'Pegawai OPD Lain' => $seesOutside] as $name => $sees) {
        $sees ? $page->assertSee($name) : $page->assertDontSee($name);
    }
})->with([
    'atasan' => [Role::Atasan, true, false, false],
    'koordinator_opd' => [Role::KoordinatorOpd, true, true, false],
    'pengelola' => [Role::Pengelola, true, true, true],
    'auditor' => [Role::Auditor, true, true, true],
])->group('scope');

it('IDP-04 atasan tidak dapat menyetujui atau menutup IDP pegawai di luar timnya', function () {
    $atasan = userWithRole(Role::Atasan, $this->opdA);
    $peer = idpWithItems(Employee::factory()->inOpd($this->opdA)->create(), 2, 'submitted');

    Livewire::actingAs($atasan)->test(TeamApprovalList::class)
        ->set('selected', [(string) $peer->id])
        ->call('approveSelected')
        ->assertNotFound();
    expect($peer->refresh()->status)->toBe(IdpStatus::Submitted);
})->group('scope');

it('IDP-04 tamu dialihkan ke halaman masuk', function () {
    $this->get(route('idp.plans.mine'))->assertRedirect(route('login'));
    $this->get(route('idp.team.index'))->assertRedirect(route('login'));
})->group('scope');
