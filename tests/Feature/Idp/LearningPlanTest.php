<?php

use App\Modules\Fondasi\Enums\DocumentType;
use App\Modules\Fondasi\Models\IssuedDocument;
use App\Modules\Fondasi\Notifications\DocumentReady;
use App\Modules\Idp\Actions\PublishLearningPlan;
use App\Modules\Idp\Documents\LearningPlanContent;
use App\Modules\Idp\Enums\IdpStatus;
use App\Modules\Idp\Livewire\IdpEditor;
use App\Modules\Idp\Models\IdpActivity;
use App\Modules\Integrasi\Enums\PositionTier;
use App\Modules\Integrasi\Models\Employee;
use App\Modules\Integrasi\Models\Position;
use App\Support\Documents\IssueDocumentJob;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    Storage::fake(config('semangat.documents.disk'));
    $this->supervisor = Employee::factory()->create(['name' => 'Atasan Pendamping']);
    $this->employee = Employee::factory()->supervisedBy($this->supervisor)->create([
        'position_id' => Position::factory()->create(['tier' => PositionTier::Fungsional])->id,
    ]);
    $this->user = employeeUser($this->employee);
    $this->idp = idpWithItems($this->employee, 2, 'approved');
    IdpActivity::query()->create(['idp_item_id' => $this->idp->items[0]->id, 'portion' => 10, 'title' => 'Pelatihan analisis kebijakan', 'planned_jp' => 16]);
});

it('IDP-05 "Terbitkan Rencana Pembelajaran" → PDF ber-QR terbit, tercatat di out_issued_documents, status active', function () {
    Livewire::actingAs($this->user)->test(IdpEditor::class)
        ->call('publish')
        ->assertHasNoErrors()
        ->assertDispatched('toast')
        ->assertSee('Unduh Rencana Pembelajaran');

    $document = IssuedDocument::query()->sole();
    expect($this->idp->refresh()->status)->toBe(IdpStatus::Active)
        ->and($document->type)->toBe(DocumentType::LearningPlan)
        ->and($document->employee_id)->toBe($this->employee->id)
        ->and($document->documentable_id)->toBe($this->idp->id);

    $pdf = Storage::disk(config('semangat.documents.disk'))->get($document->file_path);
    expect(substr((string) $pdf, 0, 5))->toBe('%PDF-')
        ->and(hash('sha256', (string) $pdf))->toBe($document->sha256)
        ->and((string) $pdf)->toContain('/Subtype /Image');
})->group('idp');

it('IDP-05 isi PDF: kompetensi prioritas, tautan RHK, aktivitas 10:20:70, target JP, teks Kompeten, URL verifikasi', function () {
    $data = [];
    view()->composer('idp::pdf.learning-plan', function ($view) use (&$data) {
        $data = $view->getData();
    });

    app(PublishLearningPlan::class)->handle($this->user, $this->idp);
    $document = $this->idp->refresh()->learningPlan;
    expect($document)->not->toBeNull();
    $html = view('idp::pdf.learning-plan', $data)->render();

    expect($html)
        ->toContain($this->idp->items[0]->competency->name)
        ->toContain($this->idp->items[1]->competency->name)
        ->toContain('RHK-1')
        ->toContain('Belum tertaut SKP')
        ->toContain('Pelatihan analisis kebijakan')
        ->toContain('10 — Pelatihan')
        ->toContain('20 JP')
        ->toContain('Atasan Pendamping')
        ->toContain('membagikan sekurang-kurangnya satu praktik baik')
        ->toContain('/verifikasi/'.$document->uuid);
})->group('idp');

it('IDP-05 teks ekspektasi "Kompeten" sesuai jenjang jabatan tampil dengan tombol "Salin"', function (PositionTier $tier, string $phrase) {
    $this->employee->position->update(['tier' => $tier]);
    app(PublishLearningPlan::class)->handle($this->user, $this->idp);

    Livewire::actingAs($this->user)->test(IdpEditor::class)
        ->assertSee('Ekspektasi khusus perilaku', false)
        ->assertSee($phrase)
        ->assertSee('Salin');
})->with([
    'pelaksana' => [PositionTier::Pelaksana, 'menunjukkan penerapannya pada pekerjaan'],
    'fungsional' => [PositionTier::Fungsional, 'standar kompetensi jabatan fungsionalnya'],
    'pengawas' => [PositionTier::Pengawas, 'mendampingi penyusunan dan pelaksanaan IDP anggota tim'],
    'administrator_jpt' => [PositionTier::AdministratorJpt, 'memastikan IDP seluruh pegawai di unitnya'],
])->group('idp');

it('IDP-05 halaman verifikasi publik menampilkan status dokumen tanpa isi', function () {
    app(PublishLearningPlan::class)->handle($this->user, $this->idp);
    $document = $this->idp->refresh()->learningPlan;

    $this->get(route('fondasi.documents.verify', $document->uuid))
        ->assertOk()->assertSee($this->employee->name)->assertSee($document->sha256)
        ->assertDontSee('Pelatihan analisis kebijakan');
})->group('idp');

it('IDP-05 pemilik mengunduh PDF; IDP belum terbit → 404', function () {
    $this->actingAs($this->user)->get(route('idp.plans.download', $this->idp))->assertNotFound();

    app(PublishLearningPlan::class)->handle($this->user, $this->idp);

    $this->actingAs($this->user)->get(route('idp.plans.download', $this->idp))
        ->assertOk()->assertHeader('Content-Type', 'application/pdf');
})->group('idp');

it('IDP-05 hanya pemilik & hanya IDP approved yang dapat diterbitkan', function () {
    $supervisorUser = employeeUser($this->supervisor);
    expect(fn () => app(PublishLearningPlan::class)->handle($supervisorUser, $this->idp))->toThrow(AuthorizationException::class);

    $this->idp->update(['status' => IdpStatus::Submitted]);
    expect(fn () => app(PublishLearningPlan::class)->handle($this->user, $this->idp->refresh()))->toThrow(AuthorizationException::class);
    expect(IssuedDocument::query()->count())->toBe(0);
})->group('idp');

it('IDP-05 penerbitan diantrekan di queue documents; status active segera, PDF menyusul dan pegawai dikabari (FON-07)', function () {
    Queue::fake();

    app(PublishLearningPlan::class)->handle($this->user, $this->idp);

    expect($this->idp->refresh()->status)->toBe(IdpStatus::Active)
        ->and(IssuedDocument::query()->count())->toBe(0);
    Queue::assertPushedOn('documents', IssueDocumentJob::class, fn (IssueDocumentJob $job) => $job->documentable->is($this->idp));

    Livewire::actingAs($this->user)->test(IdpEditor::class)
        ->assertSee('Rencana Pembelajaran sedang diterbitkan')
        ->assertDontSee('Unduh Rencana Pembelajaran');
})->group('idp');

it('IDP-05 job dokumen menerbitkan sekali (idempoten) dan mengirim notifikasi DocumentReady', function () {
    Notification::fake();
    $this->idp->update(['status' => IdpStatus::Active]);
    $job = new IssueDocumentJob(LearningPlanContent::class, $this->idp, $this->employee->id, $this->user);

    app()->call([$job, 'handle']);
    app()->call([$job, 'handle']);

    expect(IssuedDocument::query()->where('type', DocumentType::LearningPlan->value)->count())->toBe(1);
    Notification::assertSentToTimes($this->user, DocumentReady::class, 1);
})->group('idp');
