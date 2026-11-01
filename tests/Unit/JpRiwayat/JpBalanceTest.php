<?php

use App\Modules\Integrasi\Models\Employee;
use App\Modules\JpRiwayat\Models\JpRecord;
use App\Modules\JpRiwayat\Services\JpBalance;

beforeEach(function () {
    $this->employee = Employee::factory()->create();
    $this->year = (int) now()->year;
});

it('JPR-01 saldo = 12 JP verified dari target 20; 8 JP pending tidak dihitung → belum terpenuhi', function () {
    JpRecord::factory()->verified()->for($this->employee, 'employee')->create(['jp' => 12]);
    JpRecord::factory()->for($this->employee, 'employee')->create(['jp' => 8]);

    $balance = app(JpBalance::class)->forEmployee($this->employee->id, $this->year);

    expect($balance)->toMatchArray(['verified' => 12.0, 'pending' => 8.0, 'target' => 20, 'fulfilled' => false, 'percent' => 60]);
})->group('jp');

it('JPR-02 sentinel: sertifikat pending dan rejected tidak menambah saldo', function () {
    JpRecord::factory()->for($this->employee, 'employee')->create(['jp' => 30]);
    JpRecord::factory()->rejected()->for($this->employee, 'employee')->create(['jp' => 30]);

    expect(app(JpBalance::class)->forEmployee($this->employee->id, $this->year)['verified'])->toBe(0.0);
})->group('jp');

it('JPR-01 terpenuhi tepat di 20 JP; JP tahun lain dan pegawai lain tidak ikut', function () {
    JpRecord::factory()->verified()->for($this->employee, 'employee')->create(['jp' => 12.5]);
    JpRecord::factory()->verified()->for($this->employee, 'employee')->create(['jp' => 7.5]);
    JpRecord::factory()->verified()->for($this->employee, 'employee')->create(['jp' => 40, 'year' => $this->year - 1]);
    JpRecord::factory()->verified()->create(['jp' => 40]);

    $balance = app(JpBalance::class)->forEmployee($this->employee->id, $this->year);

    expect($balance['verified'])->toBe(20.0)->and($balance['fulfilled'])->toBeTrue()->and($balance['percent'])->toBe(100);
})->group('jp');
