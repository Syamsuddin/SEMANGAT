<?php

use App\Modules\Integrasi\Adapters\SkpFileImportAdapter;
use App\Modules\Integrasi\Data\SkpRecord;

it('INT-02 memetakan fixture berkas SKP ke DTO SkpRecord; kolom predikat/nilai tidak terbaca', function () {
    $records = iterator_to_array((new SkpFileImportAdapter)->fetchWorkResults(2027, skpFixture('valid.csv')), false);

    expect($records)->toHaveCount(3)->each->toBeInstanceOf(SkpRecord::class)
        ->and($records[0]->nip)->toBe('199901012010011101')
        ->and($records[0]->periodYear)->toBe(2027)
        ->and($records[0]->workResultCode)->toBe('RHK-1')
        ->and($records[0]->specialExpectation)->toBe('Kompeten')
        ->and($records[0]->dialogueNotes)->toBe('Dialog triwulan I baik')
        ->and($records[0]->rowNumber)->toBe(2)
        ->and(array_keys($records[0]->toArray()))->not->toContain('predikat')->not->toContain('nilai')
        ->and(json_encode(array_map(fn ($r) => $r->toArray(), $records)))->not->toContain('Sangat Baik');
})->group('integrasi', 'contract');

it('INT-02 berkas tidak ada atau format salah ditolak', function () {
    $adapter = new SkpFileImportAdapter;

    expect(fn () => iterator_to_array($adapter->fetchWorkResults(2027, 'imports/tidak-ada.csv')))->toThrow(InvalidArgumentException::class)
        ->and(fn () => iterator_to_array($adapter->fetchWorkResults(2027, null)))->toThrow(InvalidArgumentException::class);
})->group('integrasi', 'contract');
