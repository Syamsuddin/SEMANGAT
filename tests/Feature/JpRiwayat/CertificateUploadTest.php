<?php

use App\Modules\Integrasi\Models\Employee;
use App\Modules\JpRiwayat\Actions\UploadCertificate;
use App\Modules\JpRiwayat\Enums\JpStatus;
use App\Modules\JpRiwayat\Livewire\JpOverview;
use App\Modules\JpRiwayat\Models\Certificate;
use App\Modules\JpRiwayat\Models\JpRecord;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

beforeEach(function () {
    Storage::fake(config('semangat.jp.certificates.disk'));
    $this->employee = Employee::factory()->create();
    $this->user = employeeUser($this->employee);
});

it('JPR-01 halaman JP menampilkan "12 dari 20 JP" dan "Belum terpenuhi" untuk 12 JP verified + 8 JP pending', function () {
    JpRecord::factory()->verified()->for($this->employee, 'employee')->create(['jp' => 12]);
    JpRecord::factory()->for($this->employee, 'employee')->create(['jp' => 8]);

    Livewire::actingAs($this->user)->test(JpOverview::class)
        ->assertSee('12 dari 20 JP')
        ->assertSee('Belum terpenuhi')
        ->assertSee('8 JP menunggu verifikasi');

    $this->actingAs($this->user)->get(route('fondasi.home'))->assertOk()->assertSee('12 dari 20 JP');
})->group('jp');

it('JPR-02 unggah sertifikat luar → CatatanJP pending + berkas tersimpan berganti nama UUID', function () {
    Livewire::actingAs($this->user)->test(JpOverview::class)
        ->set('form', jpInput())
        ->set('file', certificatePdf())
        ->call('upload')
        ->assertHasNoErrors()
        ->assertDispatched('toast')
        ->assertSee('Pelatihan Analisis Kebijakan')
        ->assertSee('Menunggu verifikasi');

    $record = JpRecord::query()->with('certificate')->sole();
    expect($record->status)->toBe(JpStatus::Pending)
        ->and((float) $record->jp)->toBe(12.0)
        ->and($record->year)->toBe((int) now()->year)
        ->and($record->certificate->issuer)->toBe('BPSDM Provinsi')
        ->and($record->certificate->file_path)->toMatch('#^certificates/[0-9a-f-]{36}\.pdf$#');
    Storage::disk(config('semangat.jp.certificates.disk'))->assertExists($record->certificate->file_path);
})->group('jp');

it('JPR-02 berkas dengan hash sama ditolak "Sertifikat ini sudah pernah diunggah"', function () {
    app(UploadCertificate::class)->handle($this->user, jpInput(), certificatePdf('sama'));

    Livewire::actingAs($this->user)->test(JpOverview::class)
        ->set('form', jpInput(['title' => 'Kegiatan lain', 'activity_start' => now()->subDays(30)->toDateString(), 'activity_end' => now()->subDays(29)->toDateString()]))
        ->set('file', certificatePdf('sama'))
        ->call('upload')
        ->assertHasErrors(['file'])
        ->assertSee('Sertifikat ini sudah pernah diunggah');

    expect(JpRecord::query()->count())->toBe(1)->and(Certificate::query()->count())->toBe(1);
})->group('jp');

it('JPR-02 judul + tanggal + penyelenggara sama untuk pegawai sama ditolak; boleh lagi bila yang lama ditolak verifikator', function () {
    $first = app(UploadCertificate::class)->handle($this->user, jpInput(), certificatePdf('1'));

    expect(fn () => app(UploadCertificate::class)->handle($this->user, jpInput(), certificatePdf('2')))
        ->toThrow(ValidationException::class, 'Sertifikat ini sudah pernah diunggah');

    $first->update(['status' => JpStatus::Rejected, 'reject_reason' => 'Berkas buram']);
    app(UploadCertificate::class)->handle($this->user, jpInput(), certificatePdf('3'));
    expect(JpRecord::query()->count())->toBe(2);
})->group('jp');

it('JPR-02 sertifikat yang sama milik pegawai lain bukan duplikat', function () {
    app(UploadCertificate::class)->handle($this->user, jpInput(), certificatePdf('x'));
    app(UploadCertificate::class)->handle(employeeUser(), jpInput(), certificatePdf('x'));

    expect(JpRecord::query()->count())->toBe(2);
})->group('jp');

it('JPR-02 validasi unggahan: MIME dari isi, tanggal, JP > 0', function (array $input, UploadedFile $file, string $errorKey) {
    expect(fn () => app(UploadCertificate::class)->handle($this->user, jpInput($input), $file))
        ->toThrow(fn (ValidationException $e) => expect($e->errors())->toHaveKey($errorKey));
    expect(JpRecord::query()->count())->toBe(0);
})->with([
    'teks berkedok .pdf' => fn () => [[], UploadedFile::fake()->createWithContent('sertifikat.pdf', 'bukan pdf'), 'file'],
    'ekstensi terlarang' => fn () => [[], UploadedFile::fake()->createWithContent('sertifikat.docx', "%PDF-1.4\n"), 'file'],
    'selesai sebelum mulai' => fn () => [['activity_start' => now()->subDays(2)->toDateString(), 'activity_end' => now()->subDays(5)->toDateString()], certificatePdf(), 'activity_end'],
    'tanggal di masa depan' => fn () => [['activity_start' => now()->addDay()->toDateString(), 'activity_end' => now()->addDays(2)->toDateString()], certificatePdf(), 'activity_start'],
    'JP nol' => fn () => [['jp' => '0'], certificatePdf(), 'jp'],
])->group('jp');

it('JPR-02 gambar PNG diterima sebagai sertifikat', function () {
    app(UploadCertificate::class)->handle($this->user, jpInput(), UploadedFile::fake()->image('sertifikat.png', 60, 40));

    expect(JpRecord::query()->sole()->certificate->file_path)->toEndWith('.png');
})->group('jp');
