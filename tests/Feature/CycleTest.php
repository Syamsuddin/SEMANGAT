<?php

use App\Modules\Fondasi\Enums\DocumentType;
use App\Modules\Fondasi\Enums\Role;
use App\Modules\Fondasi\Livewire\NotificationCenter;
use App\Modules\Idp\Enums\IdpStatus;
use App\Modules\Idp\Livewire\IdpEditor;
use App\Modules\Idp\Livewire\TeamApprovalList;
use App\Modules\Idp\Models\Idp;
use App\Modules\Integrasi\Models\Employee;
use App\Modules\Integrasi\Models\OrgUnit;
use App\Modules\JpRiwayat\Livewire\CertificateVerificationList;
use App\Modules\JpRiwayat\Livewire\JpOverview;
use App\Modules\JpRiwayat\Models\JpRecord;
use App\Modules\Kompetensi\Models\Competency;
use App\Modules\Kompetensi\Models\Gap;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

/*
| E2E siklus MVP (docs/13): draf IDP → setuju → Rencana Pembelajaran → JP → check-in → tutup → Laporan Hasil Pembelajaran.
| Tanpa fake queue/event/notifikasi: job `documents` berjalan nyata (driver sync di tes), notifikasi in-app tersimpan.
*/

it('E2E IDP-01 IDP-04 IDP-05 JPR-02 IDP-06 IDP-07 JPR-05 siklus draf → setuju → Rencana → JP → check-in → tutup → Laporan', function () {
    Storage::fake(config('semangat.documents.disk'));
    Storage::fake(config('semangat.jp.certificates.disk'));
    $this->travelTo(Carbon::create((int) now()->year, 2, 3, 9));

    $opd = OrgUnit::factory()->create();
    $atasan = userWithRole(Role::Atasan, $opd);
    $employee = Employee::query()->where('supervisor_id', $atasan->employee_id)->firstOrFail();
    $employee->update(['opd_id' => $opd->id, 'org_unit_id' => $opd->id]);
    $user = employeeUser($employee);
    $koordinator = userWithRole(Role::KoordinatorOpd, $opd);
    foreach (['Analisis Kebijakan' => 2, 'Komunikasi Publik' => 1] as $name => $gap) {
        Gap::factory()->create(['employee_id' => $employee->id, 'competency_id' => Competency::factory()->create(['name' => $name])->id, 'required_level' => 3, 'actual_level' => 3 - $gap, 'gap' => $gap]);
    }
    skpWorkResults($employee, ['Tersusunnya analisis kebijakan daerah', 'Terlaksananya komunikasi publik']);

    // 1. Draf otomatis → lengkapi aktivitas → ajukan
    $editor = Livewire::actingAs($user)->test(IdpEditor::class)->call('start')->assertSee('Analisis Kebijakan');
    $idp = Idp::query()->sole();
    $itemId = $idp->items()->first()->id;
    $editor->set("activity.$itemId", ['portion' => 10, 'title' => 'Diklat Analis Kebijakan', 'planned_jp' => 24])
        ->call('addActivity', $itemId)->assertHasNoErrors()
        ->call('submit')->assertHasNoErrors();
    expect($idp->refresh()->status)->toBe(IdpStatus::Submitted);

    // 2. Atasan menyetujui (massal)
    Livewire::actingAs($atasan)->test(TeamApprovalList::class)
        ->set('selected', [(string) $idp->id])->set('note', 'Fokus pada penerapan.')
        ->call('approveSelected')->assertHasNoErrors();
    expect($idp->refresh()->status)->toBe(IdpStatus::Approved);

    // 3. Terbitkan Rencana Pembelajaran (queue documents → PDF ber-QR + notifikasi)
    Livewire::actingAs($user)->test(IdpEditor::class)->call('publish')->assertHasNoErrors()->assertSee('Unduh Rencana Pembelajaran');
    expect($idp->refresh()->status)->toBe(IdpStatus::Active)->and($idp->learningPlan?->type)->toBe(DocumentType::LearningPlan);

    // 4. JP: unggah sertifikat luar → verifikasi koord. OPD → masuk saldo
    $this->travelTo(Carbon::create((int) now()->year, 4, 20, 9));
    Livewire::actingAs($user)->test(JpOverview::class)
        ->set('form', jpInput(['title' => 'Diklat Analis Kebijakan', 'jp' => '24', 'activity_start' => now()->year.'-04-01', 'activity_end' => now()->year.'-04-05']))
        ->set('file', certificatePdf('siklus'))
        ->call('upload')->assertHasNoErrors()->assertSee('0 dari 20 JP');
    $record = JpRecord::query()->sole();
    Livewire::actingAs($koordinator)->test(CertificateVerificationList::class)->call('verify', $record->id)->assertHasNoErrors();
    Livewire::actingAs($user)->test(JpOverview::class)->assertSee('24 dari 20 JP')->assertSee('Terpenuhi');

    // 5. Check-in triwulan 2
    $this->travelTo(Carbon::create((int) now()->year, 5, 15, 9));
    Livewire::actingAs($user)->test(IdpEditor::class)
        ->set('progress', $idp->items->mapWithKeys(fn ($i) => [$i->id => 80])->all())
        ->set('checkinNote', 'Diklat selesai, mulai menyusun kajian.')
        ->call('saveCheckin')->assertHasNoErrors()->assertSee('Triwulan 2');

    // 6. Penutupan: refleksi pegawai → penilaian penerapan atasan
    $this->travelTo(Carbon::create((int) now()->year, 12, 10, 9));
    Livewire::actingAs($user)->test(IdpEditor::class)->set('reflection', 'Kajian kebijakan saya kini berbasis data.')->call('saveReflection')->assertHasNoErrors();
    Livewire::actingAs($atasan)->test(TeamApprovalList::class)
        ->set("assessments.{$idp->id}", 'Kajian dipakai dalam rapat pimpinan.')
        ->call('close', $idp->id)->assertHasNoErrors();
    expect($idp->refresh()->status)->toBe(IdpStatus::Closed);

    // 7. Laporan Hasil Pembelajaran
    Livewire::actingAs($user)->test(IdpEditor::class)->call('issueReport')->assertHasNoErrors()->assertSee('Unduh Laporan Hasil Pembelajaran');
    $report = $idp->refresh()->learningReport;
    expect($report?->type)->toBe(DocumentType::LearningReport);
    $this->actingAs($user)->get(route('jp.reports.download', $idp))->assertOk()->assertHeader('Content-Type', 'application/pdf');
    $this->get(route('fondasi.documents.verify', $report->uuid))->assertOk()->assertSee('Laporan Hasil Pembelajaran');

    // Notifikasi in-app sepanjang siklus: IDP disetujui, Rencana siap, sertifikat diverifikasi, Laporan siap.
    expect($user->notifications()->count())->toBe(4);
    Livewire::actingAs($user)->test(NotificationCenter::class)
        ->assertSee('Rencana Pembelajaran Anda sudah terbit')
        ->assertSee('Laporan Hasil Pembelajaran Anda sudah terbit')
        ->assertSee('Diklat Analis Kebijakan');
})->group('idp', 'jp');
