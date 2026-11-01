<?php

use App\Modules\Fondasi\Enums\Role;
use App\Modules\Integrasi\Models\Employee;
use App\Modules\Integrasi\Models\OrgUnit;
use App\Modules\JpRiwayat\Livewire\JpRecapReport;
use App\Modules\JpRiwayat\Models\JpRecord;
use App\Modules\JpRiwayat\Services\JpRecap;
use Livewire\Livewire;

beforeEach(function () {
    $this->opdA = OrgUnit::factory()->create(['name' => 'Dinas Alfa']);
    $this->opdB = OrgUnit::factory()->create(['name' => 'Dinas Beta']);
    // OPD A: 5 pegawai (2 memenuhi 20 JP); OPD B: 2 pegawai (1 memenuhi).
    $this->a = Employee::factory()->inOpd($this->opdA)->count(5)->sequence(fn ($s) => ['name' => 'Pegawai Alfa '.($s->index + 1)])->create();
    $this->b = Employee::factory()->inOpd($this->opdB)->count(2)->sequence(fn ($s) => ['name' => 'Pegawai Beta '.($s->index + 1)])->create();
    JpRecord::factory()->verified()->for($this->a[0], 'employee')->create(['jp' => 20]);
    JpRecord::factory()->verified()->for($this->a[1], 'employee')->create(['jp' => 24]);
    JpRecord::factory()->for($this->a[2], 'employee')->create(['jp' => 30]); // pending: tidak dihitung
    JpRecord::factory()->verified()->for($this->b[0], 'employee')->create(['jp' => 22]);
});

it('JPR-06 koord. OPD melihat rekap JP OPD-nya saja, termasuk baris per pegawai', function () {
    $koordinator = userWithRole(Role::KoordinatorOpd, $this->opdA);

    $data = app(JpRecap::class)->forUser($koordinator, (int) now()->year);

    expect($data['scope'])->toBe('opd')
        ->and(array_column($data['opds'], 'name'))->toBe(['Dinas Alfa'])
        ->and($data['opds'][0]['fulfilled'])->toBe(2)
        ->and(collect($data['employees'])->pluck('name')->filter(fn ($n) => str_starts_with($n, 'Pegawai Beta')))->toBeEmpty();

    Livewire::actingAs($koordinator)->test(JpRecapReport::class)
        ->assertSee('Pegawai Alfa 1')->assertSee('Dinas Alfa')
        ->assertDontSee('Pegawai Beta 1')->assertDontSee('Dinas Beta');
})->group('jp');

it('JPR-06 CLO melihat agregat kabupaten tanpa identitas; OPD dengan n<5 disamarkan', function () {
    $clo = userWithRole(Role::Clo, $this->opdA);

    $data = app(JpRecap::class)->forUser($clo, (int) now()->year);
    $alfa = collect($data['opds'])->firstWhere('name', 'Dinas Alfa');
    $beta = collect($data['opds'])->firstWhere('name', 'Dinas Beta');

    expect($data['scope'])->toBe('regency')
        ->and($data['masked'])->toBeTrue()
        ->and($data['employees'])->toBe([])
        ->and($alfa['fulfilled'])->toBe(2)
        ->and($beta['employees'])->toBeNull()
        ->and($beta['fulfilled'])->toBeNull();

    Livewire::actingAs($clo)->test(JpRecapReport::class)
        ->assertSee('Dinas Alfa')->assertSee('Dinas Beta')->assertSee('&lt; 5', false)
        ->assertDontSee('Pegawai Alfa 1');
})->group('jp');

it('JPR-06 pengelola melihat seluruh OPD tanpa penyamaran', function () {
    $data = app(JpRecap::class)->forUser(userWithRole(Role::Pengelola, $this->opdA), (int) now()->year);
    $beta = collect($data['opds'])->firstWhere('name', 'Dinas Beta');

    expect($data['masked'])->toBeFalse()->and($beta)->toMatchArray(['employees' => 2, 'fulfilled' => 1, 'avg_jp' => 11.0]);
})->group('jp');
