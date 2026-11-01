<?php

use App\Modules\Fondasi\Enums\Role;
use App\Modules\Integrasi\Models\Employee;
use App\Modules\Integrasi\Models\OrgUnit;
use App\Modules\JpRiwayat\Actions\UploadCertificate;
use App\Modules\JpRiwayat\Livewire\CertificateVerificationList;
use App\Modules\JpRiwayat\Models\JpRecord;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

/*
| Matriks docs/05 untuk M12 (F4), 9 peran: asn S · atasan T · koord. O · pengelola K · clo/dewan A (agregat, tanpa baris) · auditor B.
| Verifikasi sertifikat: pengelola & koord. OPD (docs/06 § 6). "rekan" = pegawai OPD A (bukan bawahan), "luar" = pegawai OPD B.
*/

beforeEach(function () {
    Storage::fake(config('semangat.jp.certificates.disk'));
    $this->opdA = OrgUnit::factory()->create();
    $this->opdB = OrgUnit::factory()->create();
});

function uploadedRecord(Employee $employee, string $title): JpRecord
{
    return app(UploadCertificate::class)->handle(employeeUser($employee), jpInput(['title' => $title]), certificatePdf($title));
}

it('JPR-04 halaman, rekap & unduhan M12 per peran sesuai matriks cakupan', function (Role $role, int $verify, int $recap, int $peer, int $outside) {
    $user = userWithRole($role, $this->opdA);
    $peerEmployee = Employee::factory()->inOpd($this->opdA)->create();
    $outsideEmployee = Employee::factory()->inOpd($this->opdB)->create();
    $peerRecord = uploadedRecord($peerEmployee, 'Diklat rekan');
    $outsideRecord = uploadedRecord($outsideEmployee, 'Diklat luar');

    $this->actingAs($user)->get(route('jp.records.mine'))->assertOk();
    $this->actingAs($user)->get(route('fondasi.notifications.index'))->assertOk();
    $this->actingAs($user)->get(route('jp.certificates.index'))->assertStatus($verify);
    $this->actingAs($user)->get(route('jp.recap.index'))->assertStatus($recap);
    $this->actingAs($user)->get(route('jp.records.employee', $peerEmployee))->assertStatus($peer);
    $this->actingAs($user)->get(route('jp.records.employee', $outsideEmployee))->assertStatus($outside);
    $this->actingAs($user)->get(route('jp.certificates.download', $peerRecord))->assertStatus($peer);
    $this->actingAs($user)->get(route('jp.certificates.download', $outsideRecord))->assertStatus($outside);
})->with([
    // peran, antrean verifikasi, rekap, JP & sertifikat rekan OPD, JP & sertifikat luar OPD
    'asn' => [Role::Asn, 403, 403, 403, 403],
    'atasan' => [Role::Atasan, 403, 403, 403, 403],
    'koordinator_opd' => [Role::KoordinatorOpd, 200, 200, 200, 403],
    'fasilitator' => [Role::Fasilitator, 403, 403, 403, 403],
    'pengelola' => [Role::Pengelola, 200, 200, 200, 200],
    'clo' => [Role::Clo, 403, 200, 403, 403],
    'dewan_pengarah' => [Role::DewanPengarah, 403, 200, 403, 403],
    'admin_teknis' => [Role::AdminTeknis, 403, 403, 403, 403],
    'auditor' => [Role::Auditor, 403, 200, 200, 200],
])->group('scope');

it('JPR-02 antrean verifikasi hanya memuat sertifikat dalam wilayah verifikator', function (Role $role, bool $seesPeer, bool $seesOutside) {
    $user = userWithRole($role, $this->opdA);
    uploadedRecord(Employee::factory()->inOpd($this->opdA)->create(['name' => 'Rekan Satu OPD']), 'Diklat rekan');
    uploadedRecord(Employee::factory()->inOpd($this->opdB)->create(['name' => 'Pegawai OPD Lain']), 'Diklat luar');

    $page = Livewire::actingAs($user)->test(CertificateVerificationList::class);
    $seesPeer ? $page->assertSee('Rekan Satu OPD') : $page->assertDontSee('Rekan Satu OPD');
    $seesOutside ? $page->assertSee('Pegawai OPD Lain') : $page->assertDontSee('Pegawai OPD Lain');
})->with([
    'koordinator_opd' => [Role::KoordinatorOpd, true, false],
    'pengelola' => [Role::Pengelola, true, true],
])->group('scope');

it('JPR-04 riwayat JP orang lain tidak bocor ke halaman JP saya', function () {
    $user = employeeUser(Employee::factory()->inOpd($this->opdA)->create());
    uploadedRecord(Employee::factory()->inOpd($this->opdA)->create(), 'Diklat rahasia rekan');

    $this->actingAs($user)->get(route('jp.records.mine'))->assertOk()->assertDontSee('Diklat rahasia rekan');
})->group('scope');
