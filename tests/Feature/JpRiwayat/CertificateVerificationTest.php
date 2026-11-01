<?php

use App\Modules\Fondasi\Enums\AuditAction;
use App\Modules\Fondasi\Enums\Role;
use App\Modules\Fondasi\Models\AuditLog;
use App\Modules\Integrasi\Models\Employee;
use App\Modules\Integrasi\Models\OrgUnit;
use App\Modules\JpRiwayat\Actions\UploadCertificate;
use App\Modules\JpRiwayat\Actions\VerifyCertificate;
use App\Modules\JpRiwayat\Enums\JpStatus;
use App\Modules\JpRiwayat\Livewire\CertificateVerificationList;
use App\Modules\JpRiwayat\Livewire\JpOverview;
use App\Modules\JpRiwayat\Notifications\CertificateRejected;
use App\Modules\JpRiwayat\Notifications\CertificateVerified;
use App\Modules\JpRiwayat\Services\JpBalance;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

beforeEach(function () {
    Storage::fake(config('semangat.jp.certificates.disk'));
    $this->opd = OrgUnit::factory()->create();
    $this->employee = Employee::factory()->inOpd($this->opd)->create(['name' => 'Pegawai Pengunggah']);
    $this->owner = employeeUser($this->employee);
    $this->record = app(UploadCertificate::class)->handle($this->owner, jpInput(['jp' => '16']), certificatePdf());
    $this->koordinator = userWithRole(Role::KoordinatorOpd, $this->opd);
});

it('JPR-02 koord. OPD memverifikasi (JP dikoreksi) → verified, masuk saldo, pegawai dikabari, tercatat audit', function () {
    Notification::fake();

    Livewire::actingAs($this->koordinator)->test(CertificateVerificationList::class)
        ->assertSee('Pegawai Pengunggah')
        ->assertSee('Diajukan: 16 JP')
        ->set("jp.{$this->record->id}", '12')
        ->call('verify', $this->record->id)
        ->assertHasNoErrors()
        ->assertDispatched('toast')
        ->assertDontSee('Pegawai Pengunggah');

    $record = $this->record->refresh();
    expect($record->status)->toBe(JpStatus::Verified)
        ->and((float) $record->jp)->toBe(12.0)
        ->and($record->verified_by)->toBe($this->koordinator->id)
        ->and(app(JpBalance::class)->forEmployee($this->employee->id, (int) now()->year)['verified'])->toBe(12.0);
    Notification::assertSentTo($this->owner, CertificateVerified::class);
    expect(AuditLog::query()->where('auditable_id', $record->id)->where('action', AuditAction::Update->value)->exists())->toBeTrue();
})->group('jp');

it('JPR-02 penolakan wajib beralasan; alasan terlihat pegawai di riwayat dan notifikasi', function () {
    Notification::fake();

    Livewire::actingAs($this->koordinator)->test(CertificateVerificationList::class)
        ->call('reject', $this->record->id)
        ->assertHasErrors(['reject_reason']);
    expect($this->record->refresh()->status)->toBe(JpStatus::Pending);

    Livewire::actingAs($this->koordinator)->test(CertificateVerificationList::class)
        ->set("reasons.{$this->record->id}", 'Nama pada sertifikat tidak sesuai')
        ->call('reject', $this->record->id)
        ->assertHasNoErrors();

    expect($this->record->refresh()->status)->toBe(JpStatus::Rejected);
    Notification::assertSentTo($this->owner, CertificateRejected::class, fn (CertificateRejected $n) => $n->toArray($this->owner)['note'] === 'Nama pada sertifikat tidak sesuai');

    Livewire::actingAs($this->owner)->test(JpOverview::class)
        ->assertSee('Ditolak')
        ->assertSee('Nama pada sertifikat tidak sesuai')
        ->assertSee('0 dari 20 JP');
})->group('jp');

it('JPR-02 pengelola memverifikasi lintas OPD; koord. OPD lain, auditor, dan pemilik sendiri tidak bisa', function () {
    $otherKoordinator = userWithRole(Role::KoordinatorOpd, OrgUnit::factory()->create());
    $auditor = userWithRole(Role::Auditor);

    foreach ([$otherKoordinator, $auditor, $this->owner] as $user) {
        expect(fn () => app(VerifyCertificate::class)->verify($user, $this->record->refresh()))->toThrow(AuthorizationException::class);
    }

    app(VerifyCertificate::class)->verify(userWithRole(Role::Pengelola), $this->record->refresh());
    expect($this->record->refresh()->status)->toBe(JpStatus::Verified);

    // sudah diverifikasi → tidak dapat diproses ulang
    expect(fn () => app(VerifyCertificate::class)->reject($this->koordinator, $this->record->refresh(), 'Terlambat diproses'))->toThrow(AuthorizationException::class);
})->group('jp');

it('JPR-02 koord. OPD tidak memverifikasi sertifikatnya sendiri dan tidak melihatnya di antrean', function () {
    $own = app(UploadCertificate::class)->handle($this->koordinator, jpInput(['title' => 'Diklat koordinator']), certificatePdf('k'));

    Livewire::actingAs($this->koordinator)->test(CertificateVerificationList::class)
        ->assertSee('Pegawai Pengunggah')
        ->assertDontSee('Diklat koordinator');
    expect(fn () => app(VerifyCertificate::class)->verify($this->koordinator, $own))->toThrow(AuthorizationException::class);
})->group('jp');

it('JPR-02 koreksi JP verifikator divalidasi (> 0)', function () {
    expect(fn () => app(VerifyCertificate::class)->verify($this->koordinator, $this->record, '0'))->toThrow(ValidationException::class);
    expect($this->record->refresh()->status)->toBe(JpStatus::Pending);
})->group('jp');
