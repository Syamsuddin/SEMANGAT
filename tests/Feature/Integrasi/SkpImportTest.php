<?php

use App\Modules\Integrasi\Actions\RunSkpImport;
use App\Modules\Integrasi\Adapters\SkpFileImportAdapter;
use App\Modules\Integrasi\Models\Employee;
use App\Modules\Integrasi\Models\SkpSummary;
use App\Modules\Integrasi\Models\SkpWorkResult;
use App\Modules\Integrasi\Models\SyncRun;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    Http::fake();
    Employee::factory()->create(['nip' => '199901012010011101']);
    Employee::factory()->create(['nip' => '199901012015011102']);
});

it('INT-02 impor berkas SKP idempoten ke salinan baca-saja; tidak ada request apa pun ke e-Kinerja', function () {
    $run = app(RunSkpImport::class)(SkpFileImportAdapter::NAME, 2027, skpFixture('valid.csv'));
    app(RunSkpImport::class)(SkpFileImportAdapter::NAME, 2027, skpFixture('valid.csv'));

    expect($run->status->value)->toBe('success')->and($run->rows_ok)->toBe(3)
        ->and(SkpSummary::query()->count())->toBe(2)
        ->and(SkpWorkResult::query()->count())->toBe(3);
    $summary = SkpSummary::query()->with('workResults')->whereRelation('employee', 'nip', '199901012010011101')->sole();
    expect($summary->period_year)->toBe(2027)->and($summary->dialogue_notes)->toBe('Dialog triwulan I baik')
        ->and($summary->workResults->pluck('code')->all())->toBe(['RHK-1', 'RHK-2'])
        ->and($summary->source)->toBe('skp_file');

    Http::assertNothingSent();
})->group('integrasi');

it('INT-02 skema SKP tidak memiliki kolom nilai/predikat (docs/02)', function () {
    $columns = [...Schema::getColumnListing('hr_skp_summaries'), ...Schema::getColumnListing('hr_skp_work_results')];

    expect(array_filter($columns, fn ($c) => preg_match('/predikat|predicate|score|nilai|rating/i', $c)))->toBe([]);
})->group('integrasi');

it('INT-02 satu baris SKP tidak valid → seluruh berkas ditolak dengan laporan per baris', function () {
    $ref = csvImport(SkpFileImportAdapter::COLUMNS, [
        ['nip' => '199901012010011101', 'kode_rhk' => 'RHK-1', 'rencana_hasil_kerja' => 'A'],
        ['nip' => '199900000000000009', 'kode_rhk' => 'RHK-1', 'rencana_hasil_kerja' => 'B'],
        ['nip' => '199901012010011101', 'kode_rhk' => 'RHK-1', 'rencana_hasil_kerja' => ''],
    ]);

    $run = app(RunSkpImport::class)(SkpFileImportAdapter::NAME, 2027, $ref);

    expect($run->status->value)->toBe('failed')->and($run->rows_failed)->toBe(2)
        ->and($run->failures->pluck('row_number')->all())->toBe([3, 4])
        ->and(SkpSummary::query()->count())->toBe(0);
})->group('integrasi');

it('INT-02 semangat:import skp butuh --period dan mencatat run di panel integrasi', function () {
    $path = base_path('tests/Fixtures/skp_file/valid.csv');

    expect(Artisan::call('semangat:import', ['target' => 'skp', 'path' => $path]))->not->toBe(0)
        ->and(Artisan::call('semangat:import', ['target' => 'skp', 'path' => $path, '--period' => '2027']))->toBe(0)
        ->and(SyncRun::query()->where('adapter', 'skp_file')->where('status', 'success')->count())->toBe(1);
})->group('integrasi');
