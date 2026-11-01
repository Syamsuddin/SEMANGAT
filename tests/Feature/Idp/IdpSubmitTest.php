<?php

use App\Modules\Fondasi\Enums\Role;
use App\Modules\Idp\Actions\SubmitIdp;
use App\Modules\Idp\Enums\IdpStatus;
use App\Modules\Idp\Livewire\IdpEditor;
use App\Modules\Idp\Livewire\UnlinkedIdpList;
use App\Modules\Idp\Models\Idp;
use App\Modules\Idp\Notifications\IdpSubmitted;
use App\Modules\Integrasi\Models\Employee;
use App\Modules\Integrasi\Models\OrgUnit;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

beforeEach(function () {
    Notification::fake();
});

it('IDP-10 butir tanpa RHK: IDP tetap terkirim dan bertanda "Belum tertaut SKP"', function () {
    $opd = OrgUnit::factory()->create();
    $atasan = userWithRole(Role::Atasan, $opd);
    $employee = Employee::query()->where('supervisor_id', $atasan->employee_id)->firstOrFail();
    $user = employeeUser($employee);
    $idp = idpWithItems($employee, 3);

    Livewire::actingAs($user)->test(IdpEditor::class)->call('submit')->assertHasNoErrors()->assertDispatched('toast');

    $idp->refresh();
    expect($idp->status)->toBe(IdpStatus::Submitted)
        ->and($idp->unlinked_items_count)->toBe(2)
        ->and($idp->supervisor_id)->toBe($atasan->employee_id)
        ->and($idp->submitted_at)->not->toBeNull();
    Notification::assertSentTo($atasan, IdpSubmitted::class);

    Livewire::actingAs($user)->test(IdpEditor::class)->assertSee('Belum tertaut SKP')->assertSee('Menunggu persetujuan');
})->group('idp');

it('IDP-10 IDP bertanda muncul di daftar "IDP tanpa tautan SKP" atasan dan koordinator OPD', function () {
    $opd = OrgUnit::factory()->create();
    $atasan = userWithRole(Role::Atasan, $opd);
    $koordinator = userWithRole(Role::KoordinatorOpd, $opd);
    $employee = Employee::query()->where('supervisor_id', $atasan->employee_id)->firstOrFail();
    $employee->update(['name' => 'Pegawai Belum Tertaut']);
    $idp = idpWithItems($employee, 2);
    app(SubmitIdp::class)->handle(employeeUser($employee), $idp);
    $linked = idpWithItems(Employee::factory()->supervisedBy($atasan->employee)->create(['name' => 'Pegawai Tertaut Penuh']), 2, 'submitted');
    $linked->items()->update(['skp_work_result_id' => $linked->items->first()->skp_work_result_id]);

    foreach ([$atasan, $koordinator] as $viewer) {
        Livewire::actingAs($viewer)->test(UnlinkedIdpList::class)
            ->assertSee('Pegawai Belum Tertaut')
            ->assertDontSee('Pegawai Tertaut Penuh');
    }
})->group('idp');

it('IDP-10 draf yang belum diajukan tidak masuk daftar tanpa tautan', function () {
    $atasan = userWithRole(Role::Atasan);
    $employee = Employee::query()->where('supervisor_id', $atasan->employee_id)->firstOrFail();
    $employee->update(['name' => 'Pegawai Masih Draf']);
    idpWithItems($employee, 2, linked: false)->update(['unlinked_items_count' => 2]);

    Livewire::actingAs($atasan)->test(UnlinkedIdpList::class)->assertDontSee('Pegawai Masih Draf')->assertSee('Semua IDP sudah tertaut ke SKP.');
})->group('idp');

it('IDP-02 pegawai tanpa atasan langsung tidak dapat mengajukan', function () {
    $employee = Employee::factory()->create(['supervisor_id' => null]);
    $idp = idpWithItems($employee, 2);

    expect(fn () => app(SubmitIdp::class)->handle(employeeUser($employee), $idp))
        ->toThrow(ValidationException::class, 'atasan langsung');
})->group('idp');

it('IDP-10 daftar tanpa tautan menampilkan state gagal dengan "Coba lagi" bila data gagal dimuat', function () {
    $atasan = userWithRole(Role::Atasan);
    Idp::addGlobalScope('gagal-uji', fn ($q) => $q->whereRaw('kolom_tidak_ada = 1'));

    try {
        Livewire::actingAs($atasan)->test(UnlinkedIdpList::class)->assertSee('Daftar IDP gagal dimuat.')->assertSee('Coba lagi');
    } finally {
        Idp::clearBootedModels();
    }
})->group('idp');
