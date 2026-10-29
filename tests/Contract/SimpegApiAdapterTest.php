<?php

use App\Modules\Integrasi\Adapters\SimpegApiAdapter;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

beforeEach(fn () => config(['semangat.simpeg.url' => 'https://simpeg.contoh.test/api', 'semangat.simpeg.token' => 'rahasia']));

it('INT-01 memetakan fixture API berhalaman ke DTO hanya dengan GET bertoken', function () {
    Http::fake([
        'simpeg.contoh.test/api/pegawai?page=1' => Http::response(file_get_contents(base_path('tests/Fixtures/simpeg_api/page1.json'))),
        'simpeg.contoh.test/api/pegawai?page=2' => Http::response(file_get_contents(base_path('tests/Fixtures/simpeg_api/page2.json'))),
    ]);

    $records = iterator_to_array((new SimpegApiAdapter)->fetch(), false);

    expect($records)->toHaveCount(3)
        ->and($records[0]->positionType)->toBe('structural')
        ->and($records[1]->supervisorNip)->toBe('199901012010011101')
        ->and($records[2]->rowNumber)->toBe(3);

    Http::assertSentCount(2);
    Http::assertSent(fn (Request $r) => $r->method() === 'GET' && $r->hasHeader('Authorization', 'Bearer rahasia'));
})->group('integrasi', 'contract');

it('INT-01 adaptor API adalah snapshot penuh dan boleh parsial', function () {
    $adapter = new SimpegApiAdapter;

    expect($adapter->isFullSnapshot())->toBeTrue()
        ->and($adapter->rejectsWholeBatchOnError())->toBeFalse();
})->group('integrasi', 'contract');

it('INT-01 gagal jelas bila URL belum dikonfigurasi', function () {
    config(['semangat.simpeg.url' => null]);

    expect(fn () => iterator_to_array((new SimpegApiAdapter)->fetch()))->toThrow(RuntimeException::class);
})->group('integrasi', 'contract');
