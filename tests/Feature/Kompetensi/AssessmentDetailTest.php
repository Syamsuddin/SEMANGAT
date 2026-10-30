<?php

use App\Modules\Fondasi\Enums\Role;
use App\Modules\Fondasi\Models\AuditLog;
use App\Modules\Integrasi\Models\Employee;
use App\Modules\Kompetensi\Livewire\AssessmentDetail;
use App\Modules\Kompetensi\Models\Assessment;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Livewire;

// FON-05 diuji ulang di F2 pada laporan asesmen rinci nyata (keputusan pemilik produk 2026-10-29).

beforeEach(function () {
    Storage::fake('sensitive');
    $this->supervisor = Employee::factory()->create();
    $this->employee = Employee::factory()->supervisedBy($this->supervisor)->create();
    $this->assessment = Assessment::factory()->create(['employee_id' => $this->employee->id, 'notes' => 'Kekuatan analitis tinggi; perlu penguatan delegasi.']);
});

function readLogs(Assessment $a)
{
    return AuditLog::query()->where('auditable_id', $a->id)->where('auditable_type', $a->getMorphClass())->where('action', 'read_sensitive');
}

it('FON-05 pengelola membuka laporan asesmen rinci tanpa alasan → ditolak, catatan tidak tampil, tanpa baris audit', function () {
    Livewire::actingAs(userWithRole(Role::Pengelola))->test(AssessmentDetail::class, ['assessment' => $this->assessment->id])
        ->assertDontSee('Kekuatan analitis tinggi')
        ->set('reason', 'singkat')->call('open')
        ->assertHasErrors('reason')
        ->assertSet('unlocked', false)
        ->assertDontSee('Kekuatan analitis tinggi');

    expect(readLogs($this->assessment)->count())->toBe(0);
})->group('kompetensi');

it('FON-05 dengan alasan → tepat 1 baris read_sensitive beralasan dan catatan tampil', function () {
    $pengelola = userWithRole(Role::Pengelola);

    Livewire::actingAs($pengelola)->test(AssessmentDetail::class, ['assessment' => $this->assessment->id])
        ->set('reason', 'Menindaklanjuti keberatan hasil asesmen pegawai')->call('open')
        ->assertSet('unlocked', true)
        ->assertSee('Kekuatan analitis tinggi');

    $log = readLogs($this->assessment)->sole();
    expect($log->reason)->toBe('Menindaklanjuti keberatan hasil asesmen pegawai')->and($log->user_id)->toBe($pengelola->id);
})->group('kompetensi');

it('FON-05 pemilik membuka laporannya sendiri tanpa alasan (tercatat) dan melihat siapa yang membuka', function () {
    $pengelola = userWithRole(Role::Pengelola);
    Livewire::actingAs($pengelola)->test(AssessmentDetail::class, ['assessment' => $this->assessment->id])
        ->set('reason', 'Verifikasi data untuk forum HCDP')->call('open');
    $owner = employeeUser($this->employee);

    Livewire::actingAs($owner)->test(AssessmentDetail::class, ['assessment' => $this->assessment->id])
        ->assertSet('unlocked', true)->assertSee('Kekuatan analitis tinggi');

    expect(readLogs($this->assessment)->where('user_id', $owner->id)->whereNull('reason')->count())->toBe(1);
    $this->actingAs($owner)->get(route('kompetensi.profile.show'))
        ->assertSee('Siapa membuka laporan asesmen saya')
        ->assertSee($pengelola->displayName())
        ->assertSee('Verifikasi data untuk forum HCDP');
})->group('kompetensi');

it('Akses F2: atasan tidak dapat membuka laporan asesmen bawahannya (403)', function () {
    $this->actingAs(employeeUser($this->supervisor))
        ->get(route('kompetensi.assessments.show', $this->assessment))->assertForbidden();
})->group('kompetensi', 'scope');

it('KOM-03 catatan & path berkas laporan tersimpan terenkripsi di basis data', function () {
    $raw = DB::table('cmp_assessments')->where('id', $this->assessment->id)->value('notes');

    expect($raw)->not->toContain('Kekuatan analitis')->and($this->assessment->refresh()->notes)->toContain('Kekuatan analitis');
})->group('kompetensi');

it('KOM-03 pengelola mengunggah psikogram → berkas terenkripsi di disk sensitive, audit tanpa nilai 🔒; unduh tercatat export', function () {
    $pengelola = userWithRole(Role::Pengelola);
    $pdf = UploadedFile::fake()->createWithContent('psikogram.pdf', '%PDF-1.4 isi psikogram rahasia');

    $component = Livewire::actingAs($pengelola)->test(AssessmentDetail::class, ['assessment' => $this->assessment->id])
        ->set('reason', 'Mengunggah laporan resmi dari UPT')->call('open')
        ->set('file', $pdf)->call('upload')->assertHasNoErrors();

    $path = $this->assessment->refresh()->report_file_path;
    expect($path)->toEndWith('.pdf.enc');
    Storage::disk('sensitive')->assertExists($path);
    expect(Storage::disk('sensitive')->get($path))->not->toContain('psikogram rahasia');
    $update = AuditLog::query()->where('auditable_id', $this->assessment->id)->where('action', 'update')->sole();
    expect(json_encode($update->changes))->not->toContain($path);

    $component->call('download')->assertFileDownloaded('laporan-asesmen-'.$this->assessment->id.'-'.Str::slug($this->assessment->competency->code).'.pdf');
    expect(AuditLog::query()->where('auditable_id', $this->assessment->id)->where('action', 'export')->count())->toBe(1);
})->group('kompetensi');

it('KOM-03 unduh sebelum membuka dengan alasan ditolak', function () {
    $this->assessment->forceFill(['report_file_path' => 'assessments/x.pdf.enc'])->save();

    Livewire::actingAs(userWithRole(Role::Auditor))->test(AssessmentDetail::class, ['assessment' => $this->assessment->id])
        ->call('download')->assertForbidden();

    expect(AuditLog::query()->where('auditable_id', $this->assessment->id)->where('action', 'export')->count())->toBe(0);
})->group('kompetensi');

it('KOM-03 auditor (baca-saja) tidak dapat mengunggah laporan rinci', function () {
    Livewire::actingAs(userWithRole(Role::Auditor))->test(AssessmentDetail::class, ['assessment' => $this->assessment->id])
        ->set('reason', 'Audit kepatuhan PDP triwulan III')->call('open')->assertSet('unlocked', true)
        ->assertDontSee('Unggah berkas laporan')
        ->set('file', UploadedFile::fake()->create('a.pdf', 10, 'application/pdf'))->call('upload')->assertForbidden();

    expect($this->assessment->refresh()->report_file_path)->toBeNull();
})->group('kompetensi');

it('KOM-03 berkas selain pdf/jpg/png ditolak', function () {
    Livewire::actingAs(userWithRole(Role::Pengelola))->test(AssessmentDetail::class, ['assessment' => $this->assessment->id])
        ->set('reason', 'Mengunggah laporan resmi dari UPT')->call('open')
        ->set('file', UploadedFile::fake()->create('makro.docm', 10, 'application/vnd.ms-word'))->call('upload')
        ->assertHasErrors('file');
})->group('kompetensi');
