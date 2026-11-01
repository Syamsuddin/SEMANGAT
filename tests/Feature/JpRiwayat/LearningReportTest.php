<?php

use App\Modules\Fondasi\Enums\DocumentType;
use App\Modules\Fondasi\Models\IssuedDocument;
use App\Modules\Fondasi\Notifications\DocumentReady;
use App\Modules\Idp\Actions\PublishLearningPlan;
use App\Modules\Idp\Enums\IdpStatus;
use App\Modules\Idp\Livewire\IdpEditor;
use App\Modules\Idp\Models\IdpActivity;
use App\Modules\Integrasi\Models\Employee;
use App\Modules\JpRiwayat\Actions\IssueLearningReport;
use App\Modules\JpRiwayat\Documents\LearningReportContent;
use App\Modules\JpRiwayat\Livewire\JpOverview;
use App\Modules\JpRiwayat\Models\JpRecord;
use App\Modules\Kompetensi\Models\Gap;
use App\Support\Enums\Confidence;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

beforeEach(function () {
    Storage::fake(config('semangat.documents.disk'));
    $this->supervisor = Employee::factory()->create(['name' => 'Atasan Penilai']);
    $this->employee = Employee::factory()->supervisedBy($this->supervisor)->create();
    $this->user = employeeUser($this->employee);
    $this->idp = idpWithItems($this->employee, 2, 'approved');
    IdpActivity::query()->create(['idp_item_id' => $this->idp->items[0]->id, 'portion' => 10, 'title' => 'Pelatihan penulisan kebijakan', 'planned_jp' => 16, 'status' => 'done']);
    app(PublishLearningPlan::class)->handle($this->user, $this->idp);
    $this->idp->refresh()->forceFill([
        'status' => IdpStatus::Closed, 'closed_at' => now(),
        'closing_reflection' => 'Saya kini menyusun telaahan staf lebih runtut.',
        'closing_assessment' => 'Telaahan yang disusun sudah dipakai pimpinan.',
    ])->save();
    $record = JpRecord::factory()->verified()->for($this->employee, 'employee')->create(['title' => 'Diklat Telaahan Staf', 'jp' => 21]);
    $record->certificate()->create(['employee_id' => $this->employee->id, 'issuer' => 'LAN RI', 'number' => 'LAN-77', 'file_path' => 'certificates/x.pdf', 'sha256' => str_repeat('a', 64)]);
    JpRecord::factory()->for($this->employee, 'employee')->create(['title' => 'Webinar belum diverifikasi', 'jp' => 2]);
});

it('JPR-05 IDP closed → "Terbitkan Laporan Hasil Pembelajaran" → PDF ber-QR tercatat sebagai learning_report, pegawai dikabari', function () {
    Notification::fake();

    Livewire::actingAs($this->user)->test(IdpEditor::class)
        ->assertSee('Terbitkan Laporan Hasil Pembelajaran')
        ->call('issueReport')
        ->assertHasNoErrors()
        ->assertDispatched('toast')
        ->assertSee('Unduh Laporan Hasil Pembelajaran');

    $document = $this->idp->refresh()->learningReport;
    expect($document->type)->toBe(DocumentType::LearningReport)
        ->and($document->employee_id)->toBe($this->employee->id)
        ->and($this->idp->learningPlan->type)->toBe(DocumentType::LearningPlan);

    $pdf = (string) Storage::disk(config('semangat.documents.disk'))->get($document->file_path);
    expect(substr($pdf, 0, 5))->toBe('%PDF-')->and(hash('sha256', $pdf))->toBe($document->sha256)->and($pdf)->toContain('/Subtype /Image');
    Notification::assertSentTo($this->user, DocumentReady::class, fn (DocumentReady $n) => $n->document->is($document));

    $this->get(route('fondasi.documents.verify', $document->uuid))->assertOk()->assertSee('Laporan Hasil Pembelajaran')->assertDontSee('Diklat Telaahan Staf');
})->group('jp');

it('JPR-05 isi Laporan: target & realisasi IDP, rekap JP, bukti, refleksi, catatan atasan, URL verifikasi', function () {
    $data = [];
    view()->composer('jp::pdf.learning-report', function ($view) use (&$data) {
        $data = $view->getData();
    });

    app(IssueLearningReport::class)->handle($this->user, $this->idp);
    $document = $this->idp->refresh()->learningReport;
    $html = view('jp::pdf.learning-report', $data)->render();

    expect($html)
        ->toContain($this->idp->items[0]->competency->name)
        ->toContain($this->idp->items[1]->competency->name)
        ->toContain('RHK-1')
        ->toContain('Pelatihan penulisan kebijakan')
        ->toContain('20 JP')
        ->toContain('21,0 JP')
        ->toContain('Terpenuhi')
        ->toContain('Diklat Telaahan Staf')
        ->toContain('LAN RI')
        ->not->toContain('Webinar belum diverifikasi')
        ->toContain('Saya kini menyusun telaahan staf lebih runtut.')
        ->toContain('Telaahan yang disusun sudah dipakai pimpinan.')
        ->toContain('Atasan Penilai')
        ->toContain('/verifikasi/'.$document->uuid);
})->group('jp');

it('JPR-05 hanya pemilik & hanya IDP closed; terbit sekali per IDP', function () {
    expect(fn () => app(IssueLearningReport::class)->handle(employeeUser($this->supervisor), $this->idp))->toThrow(AuthorizationException::class);

    $this->idp->update(['status' => IdpStatus::Active]);
    expect(fn () => app(IssueLearningReport::class)->handle($this->user, $this->idp->refresh()))->toThrow(AuthorizationException::class);
    expect(IssuedDocument::query()->where('type', DocumentType::LearningReport->value)->count())->toBe(0);

    $this->idp->update(['status' => IdpStatus::Closed]);
    app(IssueLearningReport::class)->handle($this->user, $this->idp->refresh());
    expect(fn () => app(IssueLearningReport::class)->handle($this->user, $this->idp->refresh()))->toThrow(ValidationException::class);
    expect(IssuedDocument::query()->where('type', DocumentType::LearningReport->value)->count())->toBe(1);
})->group('jp');

it('JPR-05 unduh Laporan: 404 sebelum terbit, PDF setelah terbit; atasan dalam cakupan boleh mengunduh', function () {
    $this->actingAs($this->user)->get(route('jp.reports.download', $this->idp))->assertNotFound();

    app(IssueLearningReport::class)->handle($this->user, $this->idp);

    $this->actingAs($this->user)->get(route('jp.reports.download', $this->idp))->assertOk()->assertHeader('Content-Type', 'application/pdf');
    $this->actingAs(employeeUser($this->supervisor))->get(route('jp.reports.download', $this->idp))->assertOk();
    $this->actingAs(employeeUser())->get(route('jp.reports.download', $this->idp))->assertForbidden();
})->group('jp');

it('JPR-05 notifikasi Laporan menuju halaman JP tahun periode IDP, yang memuat tombol unduh', function () {
    Notification::fake();
    app(IssueLearningReport::class)->handle($this->user, $this->idp);

    $url = route('jp.records.mine', ['tahun' => $this->idp->period_year]);
    Notification::assertSentTo($this->user, DocumentReady::class, fn (DocumentReady $n) => $n->url === $url);

    $this->actingAs($this->user)->get($url)->assertOk()
        ->assertSee('Unduh Laporan Hasil Pembelajaran')
        ->assertSee(route('jp.reports.download', $this->idp), false);
})->group('jp');

it('JPR-05 Laporan untuk IDP tahun lalu tetap dapat diterbitkan & diunduh dari halaman JP setelah pergantian tahun', function () {
    $lastYear = (int) now()->year - 1;
    $this->idp->update(['period_year' => $lastYear]);

    $page = Livewire::withQueryParams(['tahun' => $lastYear])->actingAs($this->user)->test(JpOverview::class)
        ->assertSet('year', $lastYear)
        ->assertSee('IDP '.$lastYear.' sudah ditutup.')
        ->assertSee('Terbitkan Laporan Hasil Pembelajaran')
        ->call('issueReport')
        ->assertHasNoErrors()
        ->assertDispatched('toast');

    expect($this->idp->refresh()->learningReport)->not->toBeNull();
    $page->assertSee('Unduh Laporan Hasil Pembelajaran');

    // Atasan dalam cakupan melihat & mengunduh, tetapi tidak menerbitkan atas nama pegawai.
    Livewire::withQueryParams(['tahun' => $lastYear])->actingAs(employeeUser($this->supervisor))->test(JpOverview::class, ['employee' => $this->employee])
        ->assertSee('Unduh Laporan Hasil Pembelajaran')
        ->assertDontSee('Terbitkan Laporan Hasil Pembelajaran');
})->group('jp');

it('JPR-05 pegawai lain dalam cakupan tidak dapat menerbitkan Laporan dari halaman JP pegawai', function () {
    Livewire::actingAs(employeeUser($this->supervisor))->test(JpOverview::class, ['employee' => $this->employee])
        ->assertSee('Laporan Hasil Pembelajaran belum diterbitkan pegawai.')
        ->call('issueReport')
        ->assertForbidden();
    expect($this->idp->refresh()->learningReport)->toBeNull();
})->group('jp');

it('JPR-05 Laporan menandai keyakinan level awal (docs/20: data indikatif tidak bergaya terverifikasi)', function () {
    $gap = Gap::factory()->create(['employee_id' => $this->employee->id, 'competency_id' => $this->idp->items[0]->competency_id, 'confidence' => Confidence::Provisional]);
    $this->idp->items[0]->update(['gap_id' => $gap->id]);

    $html = view('jp::pdf.learning-report', app(LearningReportContent::class)->data($this->idp->refresh()) + ['uuid' => 'x', 'verifyUrl' => 'https://contoh/verifikasi/x', 'qr' => ''])->render();

    expect($html)->toContain('Keyakinan level awal: Sementara')
        ->toContain('Level awal diisi pegawai, belum ada data asesmen');
})->group('jp');
