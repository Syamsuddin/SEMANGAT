<?php

use App\Modules\Integrasi\Actions\RunEmployeeSync;
use App\Modules\Integrasi\Adapters\SimpegApiAdapter;
use App\Modules\Integrasi\Enums\EmployeeStatus;
use App\Modules\Integrasi\Enums\SyncStatus;
use App\Modules\Integrasi\Models\Employee;
use App\Modules\Integrasi\Models\OrgUnit;
use App\Modules\Integrasi\Models\Position;
use App\Modules\Integrasi\Models\SyncRun;
use Illuminate\Support\Facades\Http;

it('INT-01/03 impor berkas valid 3 baris dua kali menghasilkan tepat 3 baris pegawai (idempoten) dengan source & synced_at', function () {
    $sync = app(RunEmployeeSync::class);

    $first = $sync('employee_file', importFixture('valid.csv'));
    $this->travel(5)->minutes();
    $second = $sync('employee_file', importFixture('valid.csv'));

    expect($first->status)->toBe(SyncStatus::Success)
        ->and($second->status)->toBe(SyncStatus::Success)
        ->and($second->rows_ok)->toBe(3)
        ->and(Employee::query()->count())->toBe(3)
        ->and(Employee::query()->whereNull('synced_at')->orWhere('source', '!=', 'employee_file')->count())->toBe(0)
        ->and(OrgUnit::query()->count())->toBe(2)
        ->and(Position::query()->count())->toBe(2);

    $staff = Employee::query()->where('nip', '199901012015011102')->firstOrFail();
    expect($staff->supervisor->nip)->toBe('199901012010011101')
        ->and($staff->synced_at->greaterThan($first->finished_at))->toBeTrue()
        ->and($staff->opd->code)->toBe('OPD-01')
        ->and($staff->orgUnit->code)->toBe('OPD-01-B1');
})->group('integrasi');

it('INT-01/03 berkas dengan 1 baris tanpa NIP ditolak seluruhnya disertai laporan per baris', function () {
    $run = app(RunEmployeeSync::class)('employee_file', importFixture('missing_nip.csv'));

    expect($run->status)->toBe(SyncStatus::Failed)
        ->and($run->rows_ok)->toBe(0)
        ->and($run->rows_failed)->toBe(1)
        ->and($run->error)->toContain('Berkas ditolak')
        ->and(Employee::query()->count())->toBe(0);

    $failure = $run->failures()->sole();
    expect($failure->row_number)->toBe(3)
        ->and($failure->error)->toContain('NIP wajib diisi')
        ->and($failure->payload['name'])->toBe('Tanpa NIP');
})->group('integrasi');

it('INT-01/03 impor berkas tidak menonaktifkan pegawai yang tidak tercantum', function () {
    $sync = app(RunEmployeeSync::class);
    $sync('employee_file', importFixture('valid.csv'));
    $sync('employee_file', importRows([employeeRow('199901012010011101')]));

    expect(Employee::query()->where('status', EmployeeStatus::Active->value)->count())->toBe(3);
})->group('integrasi');

it('INT-01 sinkron API menonaktifkan pegawai yang hilang dari snapshot penuh dan menulis baris valid saat sebagian gagal', function () {
    config(['semangat.simpeg.url' => 'https://simpeg.contoh.test/api']);
    $gone = Employee::factory()->create(['source' => SimpegApiAdapter::NAME]);

    Http::fake(['simpeg.contoh.test/*' => Http::sequence()
        ->push(['data' => [
            employeeRow('199901012010011101'),
            employeeRow('199901012015011102', ['nip_atasan' => '199901012010011101']),
        ], 'meta' => ['last_page' => 1]])
        ->push(['data' => [
            employeeRow('199901012010011101', ['nama' => 'Nama Baru']),
            employeeRow('123'),
        ], 'meta' => ['last_page' => 1]]),
    ]);
    $run = app(RunEmployeeSync::class)(SimpegApiAdapter::NAME);

    expect($run->status)->toBe(SyncStatus::Success)
        ->and($gone->refresh()->status)->toBe(EmployeeStatus::Inactive);

    $partial = app(RunEmployeeSync::class)(SimpegApiAdapter::NAME);

    expect($partial->status)->toBe(SyncStatus::Partial)
        ->and($partial->rows_ok)->toBe(1)
        ->and($partial->rows_failed)->toBe(1)
        ->and(Employee::query()->where('nip', '199901012010011101')->value('name'))->toBe('Nama Baru')
        // run parsial tidak menonaktifkan siapa pun
        ->and(Employee::query()->where('nip', '199901012015011102')->value('status'))->toBe(EmployeeStatus::Active);
})->group('integrasi');

it('INT-08 kegagalan sumber dicatat sebagai run failed dan data lama tetap', function () {
    config(['semangat.simpeg.url' => 'https://simpeg.contoh.test/api']);
    $existing = Employee::factory()->create(['source' => SimpegApiAdapter::NAME]);
    Http::fake(['simpeg.contoh.test/*' => Http::response('down', 503)]);

    expect(fn () => app(RunEmployeeSync::class)(SimpegApiAdapter::NAME))->toThrow(Exception::class);

    $run = SyncRun::query()->latest('id')->firstOrFail();
    expect($run->status)->toBe(SyncStatus::Failed)
        ->and($run->finished_at)->not->toBeNull()
        ->and($run->error)->not->toBeEmpty()
        ->and($existing->refresh()->status)->toBe(EmployeeStatus::Active);
})->group('integrasi');

it('INT-03 perintah semangat:sync employees --file mengimpor berkas', function () {
    $this->artisan('semangat:sync', ['target' => 'employees', '--file' => base_path('tests/Fixtures/employee_file/valid.csv')])
        ->expectsOutputToContain('success')
        ->assertSuccessful();

    expect(Employee::query()->count())->toBe(3);
})->group('integrasi');
