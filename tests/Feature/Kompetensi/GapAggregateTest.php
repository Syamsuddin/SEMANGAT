<?php

use App\Modules\Fondasi\Enums\Role;
use App\Modules\Integrasi\Models\Employee;
use App\Modules\Integrasi\Models\OrgUnit;
use App\Modules\Kompetensi\Livewire\GapAggregate;
use App\Modules\Kompetensi\Models\Competency;
use App\Modules\Kompetensi\Models\Gap;
use App\Modules\Kompetensi\Services\GapAggregator;
use App\Support\Enums\Confidence;
use Livewire\Livewire;

beforeEach(function () {
    $this->opdA = OrgUnit::factory()->create(['name' => 'Dinas Alfa']);
    $this->opdB = OrgUnit::factory()->create(['name' => 'Dinas Beta']);
    $this->competency = Competency::factory()->create(['code' => 'K-AGG', 'name' => 'Analisis data']);
    // OPD A: 6 pegawai bergap; OPD B: 2 pegawai bergap (n<5).
    foreach ([[$this->opdA, 6], [$this->opdB, 2]] as [$opd, $n]) {
        foreach (Employee::factory()->inOpd($opd)->count($n)->create() as $i => $e) {
            Gap::factory()->create(['employee_id' => $e->id, 'competency_id' => $this->competency->id, 'gap' => 2,
                'confidence' => $i === 0 ? Confidence::Verified : Confidence::Indicative]);
        }
    }
    $inactive = Employee::factory()->inOpd($this->opdA)->inactive()->create();
    Gap::factory()->create(['employee_id' => $inactive->id, 'competency_id' => $this->competency->id]);
});

it('KOM-08 koordinator OPD melihat agregat OPD-nya saja', function () {
    $data = app(GapAggregator::class)->forUser(userWithRole(Role::KoordinatorOpd, $this->opdA));

    expect($data['scope'])->toBe('opd')->and($data['masked'])->toBeFalse()
        ->and($data['employees'])->toBe(6)
        ->and($data['competencies'][0])->toMatchArray(['code' => 'K-AGG', 'employees' => 6, 'avg_gap' => 2.0, 'verified' => 1, 'indicative' => 5])
        ->and(array_column($data['opds'], 'name'))->toBe(['Dinas Alfa']);
})->group('kompetensi');

it('KOM-08 pengelola melihat agregat kabupaten per OPD tanpa penyamaran; pegawai nonaktif tidak dihitung', function () {
    $data = app(GapAggregator::class)->forUser(userWithRole(Role::Pengelola));

    expect($data['scope'])->toBe('regency')->and($data['masked'])->toBeFalse()->and($data['employees'])->toBe(8)
        ->and($data['opds'])->toBe([
            ['name' => 'Dinas Alfa', 'employees' => 6, 'gaps' => 6],
            ['name' => 'Dinas Beta', 'employees' => 2, 'gaps' => 2],
        ]);
})->group('kompetensi');

it('KOM-08 CLO melihat agregat kabupaten dengan sel n<5 disamarkan dan tanpa nama individu', function () {
    $clo = userWithRole(Role::Clo);
    $data = app(GapAggregator::class)->forUser($clo);

    expect($data['masked'])->toBeTrue()
        ->and($data['opds'])->toBe([
            ['name' => 'Dinas Alfa', 'employees' => 6, 'gaps' => 6],
            ['name' => 'Dinas Beta', 'employees' => null, 'gaps' => null],
        ]);

    $someone = Employee::query()->where('opd_id', $this->opdB->id)->first();
    Livewire::actingAs($clo)->test(GapAggregate::class)
        ->assertSee('Analisis data')->assertSee('< 5')->assertDontSee($someone->name);
})->group('kompetensi');
