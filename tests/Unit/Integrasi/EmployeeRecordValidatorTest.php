<?php

use App\Modules\Integrasi\Data\SourceRowMapper;
use App\Modules\Integrasi\Services\EmployeeRecordValidator;

$validRow = fn (array $o = []) => SourceRowMapper::map(2, employeeRow('199901012015011102', $o));

it('INT-03 menerima baris lengkap dan memetakan nilai berbahasa Indonesia ke kode internal', function () use ($validRow) {
    $record = $validRow(['status' => 'Nonaktif', 'jenis_jabatan' => 'Pelaksana', 'kode_jabatan' => 'J1', 'nama_jabatan' => 'Pengadministrasi', 'jenjang_jabatan' => 'pelaksana']);
    $result = (new EmployeeRecordValidator)->validate([$record]);

    expect($result->errors)->toBeEmpty()
        ->and($record->status)->toBe('inactive')
        ->and($record->positionType)->toBe('executor');
})->group('integrasi');

it('INT-03 menolak baris tanpa NIP, NIP bukan 18 digit, dan NIP ganda', function () use ($validRow) {
    $v = new EmployeeRecordValidator;

    expect($v->validate([$validRow(['nip' => ''])])->errors[0]->message())->toContain('NIP wajib diisi')
        ->and($v->validate([$validRow(['nip' => '12345'])])->errors[0]->message())->toContain('18 digit')
        ->and($v->validate([$validRow(), $validRow()])->errors)->toHaveCount(2);
})->group('integrasi');

it('INT-03 menolak NIP berformat angka dari spreadsheet (presisi hilang)', function () {
    $record = SourceRowMapper::map(2, ['nip' => 1.99901012015011E17] + employeeRow('x'));

    expect((new EmployeeRecordValidator)->validate([$record])->errors[0]->message())->toContain('berformat teks');
})->group('integrasi');

it('INT-03 menolak atasan yang tidak dikenal, atasan diri sendiri, dan enum tak dikenal', function () use ($validRow) {
    $v = new EmployeeRecordValidator;

    expect($v->validate([$validRow(['nip_atasan' => '199901012010011199'])])->errors[0]->message())->toContain('tidak ditemukan')
        ->and($v->validate([$validRow(['nip_atasan' => '199901012015011102'])])->errors[0]->message())->toContain('tidak boleh sama')
        ->and($v->validate([$validRow(['jenis_pegawai' => 'honorer'])])->errors[0]->message())->toContain('pns atau pppk')
        ->and($v->validate([$validRow(['kode_jabatan' => 'J1', 'nama_jabatan' => 'X', 'jenis_jabatan' => 'lainnya', 'jenjang_jabatan' => 'pelaksana'])])->errors[0]->message())->toContain('Jenis jabatan');
})->group('integrasi');

it('INT-03 menerima atasan yang sudah ada di data pegawai walau tidak ada di berkas', function () use ($validRow) {
    $result = (new EmployeeRecordValidator)->validate(
        [$validRow(['nip_atasan' => '199901012010011199'])],
        ['199901012010011199' => true],
    );

    expect($result->hasErrors())->toBeFalse();
})->group('integrasi');
