<?php

use App\Modules\Fondasi\Enums\Role;
use App\Modules\Integrasi\Jobs\SyncEmployees;
use App\Modules\Integrasi\Livewire\EmployeeImport;
use App\Modules\Integrasi\Livewire\HealthPanel;
use App\Modules\Integrasi\Models\SyncFailure;
use App\Modules\Integrasi\Models\SyncRun;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

it('INT-08 panel menampilkan run gagal: status, waktu, jumlah gagal, dan tombol ulang', function () {
    $this->travelTo(now()->setDate(2026, 10, 29)->setTime(14, 30));
    $run = SyncRun::factory()->failed(2, 'Berkas ditolak: 2 baris tidak valid.')->create(['started_at' => now()]);
    SyncFailure::query()->create(['sync_run_id' => $run->id, 'row_number' => 3, 'payload' => [], 'error' => 'NIP wajib diisi']);

    $this->actingAs(userWithRole(Role::Pengelola))
        ->get(route('integrasi.panel'))
        ->assertOk()
        ->assertSee('Gagal')
        ->assertSee('29 Okt 2026, 14.30')
        ->assertSee('0 baris berhasil · 2 baris gagal')
        ->assertSee('Berkas ditolak: 2 baris tidak valid.')
        ->assertSee('Ulangi');
})->group('integrasi');

it('INT-08 tombol ulang mengantrekan sinkron dengan sumber yang sama dan menandai baris gagal', function () {
    Queue::fake();
    $run = SyncRun::factory()->failed()->create(['source_ref' => 'imports/abc.csv']);
    $failure = SyncFailure::query()->create(['sync_run_id' => $run->id, 'row_number' => 2, 'payload' => [], 'error' => 'x']);

    Livewire::actingAs(userWithRole(Role::Pengelola))
        ->test(HealthPanel::class)
        ->call('retry', $run->id)
        ->assertDispatched('toast');

    Queue::assertPushed(SyncEmployees::class, fn (SyncEmployees $job) => $job->adapter === 'employee_file' && $job->sourceRef === 'imports/abc.csv');
    expect($failure->refresh()->retried_at)->not->toBeNull();
})->group('integrasi');

it('INT-08 CLO dan auditor melihat panel tetapi tidak dapat mengulang sinkron', function (Role $role) {
    Queue::fake();
    $run = SyncRun::factory()->failed()->create();

    Livewire::actingAs(userWithRole($role))
        ->test(HealthPanel::class)
        ->assertDontSee('Ulangi')
        ->call('retry', $run->id)
        ->assertForbidden();

    Queue::assertNothingPushed();
})->with([Role::Clo, Role::Auditor])->group('integrasi');

it('INT-08 panel kosong menampilkan empty state', function () {
    $this->actingAs(userWithRole(Role::Pengelola))
        ->get(route('integrasi.panel'))
        ->assertOk()
        ->assertSee('Belum ada sinkron atau impor.');
})->group('integrasi');

it('INT-03 unggah berkas menyimpan dengan nama UUID dan mengantrekan impor', function () {
    Queue::fake();
    Storage::fake(config('semangat.imports.disk'));
    $file = UploadedFile::fake()->createWithContent('pegawai.csv', (string) file_get_contents(base_path('tests/Fixtures/employee_file/valid.csv')));

    Livewire::actingAs(userWithRole(Role::Pengelola))
        ->test(EmployeeImport::class)
        ->set('file', $file)
        ->call('import')
        ->assertHasNoErrors()
        ->assertRedirect(route('integrasi.panel'));

    Queue::assertPushed(SyncEmployees::class, function (SyncEmployees $job) {
        Storage::disk(config('semangat.imports.disk'))->assertExists((string) $job->sourceRef);

        return $job->adapter === 'employee_file' && preg_match('#^imports/[0-9a-f-]{36}\.csv$#', (string) $job->sourceRef) === 1;
    });
})->group('integrasi');

it('INT-03 unggahan selain XLSX/CSV ditolak', function () {
    Queue::fake();

    Livewire::actingAs(userWithRole(Role::Pengelola))
        ->test(EmployeeImport::class)
        ->set('file', UploadedFile::fake()->create('pegawai.pdf', 10, 'application/pdf'))
        ->call('import')
        ->assertHasErrors('file');

    Queue::assertNothingPushed();
})->group('integrasi');
