<?php

use App\Modules\Integrasi\Adapters\EmployeeFileImportAdapter;
use App\Modules\Integrasi\Data\EmployeeRecord;
use App\Modules\Integrasi\Data\SourceRowMapper;
use Illuminate\Support\Facades\Storage;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;

it('INT-03 memetakan fixture CSV ke DTO EmployeeRecord', function () {
    $records = iterator_to_array((new EmployeeFileImportAdapter)->fetch(importFixture('valid.csv')), false);

    expect($records)->toHaveCount(3)
        ->each->toBeInstanceOf(EmployeeRecord::class)
        ->and($records[0]->nip)->toBe('199901012010011101')
        ->and($records[0]->positionType)->toBe('structural')
        ->and($records[0]->rowNumber)->toBe(2)
        ->and($records[1]->supervisorNip)->toBe('199901012010011101')
        ->and($records[2]->employmentType)->toBe('pppk')
        ->and($records[2]->positionCode)->toBeNull();
})->group('integrasi', 'contract');

it('INT-03 membaca XLSX dengan kolom NIP bertipe teks', function () {
    $ref = 'imports/uji.xlsx';
    $path = Storage::disk(config('semangat.imports.disk'))->path($ref);
    @mkdir(dirname($path), 0777, true);
    $writer = new Writer;
    $writer->openToFile($path);
    $writer->addRow(Row::fromValues(SourceRowMapper::COLUMNS));
    $writer->addRow(Row::fromValues(array_values(array_replace(array_fill_keys(SourceRowMapper::COLUMNS, ''), employeeRow('199901012015011102')))));
    $writer->close();

    $records = iterator_to_array((new EmployeeFileImportAdapter)->fetch($ref), false);

    expect($records)->toHaveCount(1)->and($records[0]->nip)->toBe('199901012015011102');
})->group('integrasi', 'contract');

it('INT-03 adaptor berkas bukan snapshot penuh dan menolak seluruh batch bila ada baris rusak', function () {
    $adapter = new EmployeeFileImportAdapter;

    expect($adapter->isFullSnapshot())->toBeFalse()
        ->and($adapter->rejectsWholeBatchOnError())->toBeTrue();
})->group('integrasi', 'contract');

it('INT-03 menolak berkas yang tidak ada atau berformat lain', function () {
    expect(fn () => iterator_to_array((new EmployeeFileImportAdapter)->fetch('imports/tidak-ada.csv')))
        ->toThrow(InvalidArgumentException::class);

    Storage::disk(config('semangat.imports.disk'))->put('imports/x.pdf', 'bukan spreadsheet');
    expect(fn () => iterator_to_array((new EmployeeFileImportAdapter)->fetch('imports/x.pdf')))
        ->toThrow(InvalidArgumentException::class);
})->group('integrasi', 'contract');
