<?php

use App\Modules\Fondasi\Enums\Role;
use App\Modules\Fondasi\Models\AuditLog;
use App\Modules\Integrasi\Models\Employee;
use App\Modules\Kompetensi\Actions\ImportCompetencyFile as Import;
use App\Modules\Kompetensi\Enums\AssessmentMethod;
use App\Modules\Kompetensi\Enums\CompetencyType;
use App\Modules\Kompetensi\Events\AssessmentRecorded;
use App\Modules\Kompetensi\Livewire\CompetencyImport;
use App\Modules\Kompetensi\Models\Assessment;
use App\Modules\Kompetensi\Models\Competency;
use App\Modules\Kompetensi\Models\JobStandard;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;

beforeEach(fn () => $this->pengelola = userWithRole(Role::Pengelola));

function dictionaryRows(): array
{
    return [
        ['kode' => 'MNJ-01', 'nama' => 'Integritas', 'jenis' => 'manajerial', 'level_1' => 'Memahami', 'level_3' => 'Menerapkan'],
        ['kode' => 'TKN-07', 'nama' => 'Analisis kebijakan', 'jenis' => 'teknis', 'instansi_pembina' => 'LAN', 'aktif' => 'ya'],
    ];
}

it('KOM-02 impor kamus kompetensi idempoten: impor dua kali → tepat 2 kompetensi, diperbarui sesuai berkas', function () {
    $import = app(Import::class);
    expect($import(Import::DICTIONARY, csvImport(Import::COLUMNS[Import::DICTIONARY], dictionaryRows()), $this->pengelola)->succeeded())->toBeTrue();

    $rows = dictionaryRows();
    $rows[1]['nama'] = 'Analisis kebijakan publik';
    $report = $import(Import::DICTIONARY, csvImport(Import::COLUMNS[Import::DICTIONARY], $rows), $this->pengelola);

    expect($report->rowsOk)->toBe(2)->and(Competency::query()->count())->toBe(2);
    $tkn = Competency::query()->where('code', 'TKN-07')->sole();
    expect($tkn->name)->toBe('Analisis kebijakan publik')->and($tkn->type)->toBe(CompetencyType::Technical)->and($tkn->steward)->toBe('LAN')
        ->and(Competency::query()->where('code', 'MNJ-01')->sole()->level_descriptors)->toBe(['1' => 'Memahami', '3' => 'Menerapkan']);
})->group('kompetensi');

it('KOM-02 satu baris kamus tidak valid → seluruh berkas ditolak dengan laporan per baris', function () {
    $rows = [...dictionaryRows(), ['kode' => 'X-1', 'nama' => '', 'jenis' => 'rahasia'], ['kode' => 'MNJ-01', 'nama' => 'Dobel', 'jenis' => 'teknis']];

    $report = app(Import::class)(Import::DICTIONARY, csvImport(Import::COLUMNS[Import::DICTIONARY], $rows), $this->pengelola);

    expect($report->succeeded())->toBeFalse()
        ->and(array_keys($report->errors))->toBe([4, 5])
        ->and(implode(' ', $report->errors[4]))->toContain('nama')->toContain('manajerial, sosiokultural, atau teknis')
        ->and(implode(' ', $report->errors[5]))->toContain('Duplikat dari baris 2')
        ->and(Competency::query()->count())->toBe(0);
})->group('kompetensi');

it('KOM-01 impor SKJ: butir dikelompokkan per jabatan & tanggal berlaku; impor ulang mengganti butir', function () {
    [$a, $b] = [Competency::factory()->create(['code' => 'K-A']), Competency::factory()->create(['code' => 'K-B'])];
    $columns = Import::COLUMNS[Import::STANDARD];
    $row = fn ($code, $level) => ['kode_jabatan' => 'JAB-9', 'nama_skj' => 'SKJ Analis', 'berlaku_mulai' => '2026-01-01', 'kode_kompetensi' => $code, 'level' => $level];
    $import = app(Import::class);

    expect($import(Import::STANDARD, csvImport($columns, [$row('K-A', 3), $row('K-B', 2)]), $this->pengelola)->succeeded())->toBeTrue();
    expect($import(Import::STANDARD, csvImport($columns, [$row('K-A', 4)]), $this->pengelola)->succeeded())->toBeTrue();

    $standard = JobStandard::query()->with('items')->sole();
    expect($standard->position_code)->toBe('JAB-9')
        ->and($standard->items->pluck('required_level', 'competency_id')->all())->toBe([$a->id => 4]);
})->group('kompetensi');

it('KOM-01 SKJ dengan kode kompetensi tak dikenal, level 6, atau tanggal salah → ditolak utuh', function () {
    Competency::factory()->create(['code' => 'K-A']);
    $rows = [
        ['kode_jabatan' => 'J1', 'nama_skj' => 'S', 'berlaku_mulai' => '2026-01-01', 'kode_kompetensi' => 'K-A', 'level' => '3'],
        ['kode_jabatan' => 'J1', 'nama_skj' => 'S', 'berlaku_mulai' => '2026-02-30', 'kode_kompetensi' => 'K-Z', 'level' => '6'],
    ];

    $report = app(Import::class)(Import::STANDARD, csvImport(Import::COLUMNS[Import::STANDARD], $rows), $this->pengelola);

    expect(array_keys($report->errors))->toBe([3])->and(count($report->errors[3]))->toBe(3)
        ->and(JobStandard::query()->count())->toBe(0);
})->group('kompetensi');

it('KOM-03 impor hasil uji: metode → keyakinan, catatan terenkripsi, audit per baris, memicu hitung ulang; idempoten', function () {
    Event::fake([AssessmentRecorded::class]);
    $employee = Employee::factory()->create();
    Competency::factory()->create(['code' => 'K-A']);
    $rows = [['nip' => $employee->nip, 'kode_kompetensi' => 'K-A', 'level' => '3', 'metode' => 'uji_kompetensi_jf',
        'lembaga' => 'Instansi Pembina', 'tanggal_uji' => '2026-03-01', 'berlaku_sampai' => '2029-03-01', 'catatan' => 'Catatan penilai, rahasia']];
    $import = app(Import::class);

    $import(Import::ASSESSMENT, csvImport(Import::COLUMNS[Import::ASSESSMENT], $rows), $this->pengelola);
    $rows[0]['level'] = '4';
    $import(Import::ASSESSMENT, csvImport(Import::COLUMNS[Import::ASSESSMENT], $rows), $this->pengelola);

    $assessment = Assessment::query()->sole();
    expect($assessment->level)->toBe(4)
        ->and($assessment->method)->toBe(AssessmentMethod::JfCompetencyTest)
        ->and($assessment->confidence->value)->toBe('verified')
        ->and($assessment->notes)->toBe('Catatan penilai, rahasia')
        ->and(AuditLog::query()->where('auditable_id', $assessment->id)->pluck('action')->map->value->all())->toBe(['create', 'update']);
    Event::assertDispatched(AssessmentRecorded::class, fn ($e) => $e->employeeId === $employee->id);
})->group('kompetensi');

it('KOM-03 hasil uji: NIP tak dikenal, asesmen mandiri lewat impor, dan berlaku sebelum tanggal uji ditolak', function () {
    $employee = Employee::factory()->create();
    Competency::factory()->create(['code' => 'K-A']);
    $base = ['nip' => $employee->nip, 'kode_kompetensi' => 'K-A', 'level' => '3', 'metode' => 'assessment_center', 'tanggal_uji' => '2026-03-01'];
    $rows = [
        ['nip' => '199900000000000001'] + $base,
        ['metode' => 'self_assessment'] + $base,
        ['berlaku_sampai' => '2026-02-01'] + $base,
    ];

    $report = app(Import::class)(Import::ASSESSMENT, csvImport(Import::COLUMNS[Import::ASSESSMENT], $rows), $this->pengelola);

    expect(array_keys($report->errors))->toBe([2, 3, 4])->and(Assessment::query()->count())->toBe(0);
})->group('kompetensi');

it('KOM-02 berkas tanpa kolom wajib di baris judul ditolak', function () {
    $report = app(Import::class)(Import::DICTIONARY, csvImport(['kode', 'nama'], [['kode' => 'A', 'nama' => 'B']]), $this->pengelola);

    expect($report->error)->toContain('jenis');
})->group('kompetensi');

it('KOM-02 halaman impor pengelola: unggah CSV, laporan sukses/gagal tampil', function () {
    $csv = "kode,nama,jenis\nMNJ-01,Integritas,manajerial\n";

    Livewire::actingAs($this->pengelola)->test(CompetencyImport::class)
        ->set('kind', 'kamus')
        ->set('file', UploadedFile::fake()->createWithContent('kamus.csv', $csv))
        ->call('import')
        ->assertHasNoErrors()
        ->assertSee('1 baris berhasil diimpor');

    Livewire::actingAs($this->pengelola)->test(CompetencyImport::class)
        ->set('kind', 'kamus')
        ->set('file', UploadedFile::fake()->createWithContent('kamus.csv', "kode,nama,jenis\nX,,teknis\n"))
        ->call('import')
        ->assertSee('Berkas ditolak: 1 baris tidak valid')
        ->assertSee('Baris 2');

    expect(Competency::query()->count())->toBe(1);
})->group('kompetensi');

it('KOM-02 semangat:import kamus via CLI wajib pengelola berwenang', function () {
    $path = tempnam(sys_get_temp_dir(), 'kamus').'.csv';
    file_put_contents($path, "kode,nama,jenis\nMNJ-01,Integritas,manajerial\n");

    expect(Artisan::call('semangat:import', ['target' => 'kamus', 'path' => $path, '--by' => 'tidak-ada']))->not->toBe(0)
        ->and(Artisan::call('semangat:import', ['target' => 'kamus', 'path' => $path, '--by' => $this->pengelola->username]))->toBe(0)
        ->and(Competency::query()->count())->toBe(1);
})->group('kompetensi');
