<?php

use App\Modules\Fondasi\Actions\AssignRole;
use App\Modules\Fondasi\Actions\RevokeRoleAssignment;
use App\Modules\Fondasi\Enums\Role;
use App\Modules\Fondasi\Models\RoleAssignment;
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

it('JPR-06 CLO melihat agregat kabupaten tanpa identitas; OPD dengan n<5 disamarkan, OPD lain ikut disamarkan agar total tak membukanya', function () {
    $clo = userWithRole(Role::Clo, $this->opdA);

    $data = app(JpRecap::class)->forUser($clo, (int) now()->year);
    $alfa = collect($data['opds'])->firstWhere('name', 'Dinas Alfa');
    $beta = collect($data['opds'])->firstWhere('name', 'Dinas Beta');

    // Total − Alfa = Beta bila Alfa tampil; maka Alfa disamarkan komplementer, total tetap tampil (sisa = 2 OPD, n ≥ 5).
    expect($data['scope'])->toBe('regency')
        ->and($data['masked'])->toBeTrue()
        ->and($data['employees'])->toBe([])
        ->and($beta)->toMatchArray(['employees' => null, 'fulfilled' => null, 'avg_jp' => null, 'mask' => JpRecap::MASK_SMALL])
        ->and($alfa)->toMatchArray(['employees' => null, 'fulfilled' => null, 'avg_jp' => null, 'mask' => JpRecap::MASK_COMPLEMENT])
        ->and($data['total']['employees'])->toBe(8)
        ->and($data['total']['fulfilled'])->toBe(3);

    Livewire::actingAs($clo)->test(JpRecapReport::class)
        ->assertSee('Dinas Alfa')->assertSee('Dinas Beta')->assertSee('&lt; 5', false)->assertSee('Disamarkan')
        ->assertDontSee('Pegawai Alfa 1');
})->group('jp');

it('JPR-06 supresi komplementer: total dikurangi OPD yang tampak tidak dapat mengungkap OPD n<5', function () {
    $gamma = OrgUnit::factory()->create(['name' => 'Dinas Gamma']);
    Employee::factory()->inOpd($gamma)->count(9)->create();
    $clo = userWithRole(Role::Clo, $this->opdA);

    $data = app(JpRecap::class)->forUser($clo, (int) now()->year);
    $rows = collect($data['opds'])->keyBy('name');
    $shown = $rows->filter(fn (array $row) => $row['employees'] !== null);
    $hidden = $rows->filter(fn (array $row) => $row['employees'] === null);

    expect($shown->keys()->all())->toBe(['Dinas Gamma'])
        ->and($hidden->keys()->sort()->values()->all())->toBe(['Dinas Alfa', 'Dinas Beta'])
        ->and($rows['Dinas Alfa']['mask'])->toBe(JpRecap::MASK_COMPLEMENT)
        ->and($rows['Dinas Beta']['mask'])->toBe(JpRecap::MASK_SMALL)
        ->and($data['total']['employees'])->toBe(17)
        // sisa yang dapat dihitung = gabungan ≥ 2 OPD dengan n ≥ 5, bukan OPD kecil itu sendiri
        ->and($data['total']['employees'] - $shown->sum('employees'))->toBe(8);
})->group('jp');

it('JPR-06 koord. OPD yang juga CLO tetap melihat baris pegawai OPD penugasannya di samping agregat kabupaten', function () {
    $user = userWithRole(Role::KoordinatorOpd, $this->opdA);
    app(AssignRole::class)($user, Role::Clo, 'Penugasan uji');

    $data = app(JpRecap::class)->forUser($user->refresh(), (int) now()->year);
    $names = collect($data['employees'])->pluck('name');

    expect($data['scope'])->toBe('regency')
        ->and($data['masked'])->toBeTrue()
        ->and($names)->toContain('Pegawai Alfa 1')
        ->and($names->filter(fn ($n) => str_starts_with($n, 'Pegawai Beta')))->toBeEmpty();
})->group('jp');

it('JPR-06 izin rekap dicek di setiap permintaan Livewire — dicabut saat halaman terbuka → pembaruan ditolak', function () {
    $koordinator = userWithRole(Role::KoordinatorOpd, $this->opdA);
    $page = Livewire::actingAs($koordinator)->test(JpRecapReport::class)->assertSee('Pegawai Alfa 1');

    RoleAssignment::query()->where('user_id', $koordinator->id)->get()
        ->each(fn (RoleAssignment $a) => app(RevokeRoleAssignment::class)($a, 'Uji cabut'));
    auth()->setUser($koordinator->refresh());

    $page->set('year', (int) now()->year - 1)->assertForbidden();
})->group('jp');

it('JPR-06 pengelola melihat seluruh OPD tanpa penyamaran', function () {
    $data = app(JpRecap::class)->forUser(userWithRole(Role::Pengelola, $this->opdA), (int) now()->year);
    $beta = collect($data['opds'])->firstWhere('name', 'Dinas Beta');

    expect($data['masked'])->toBeFalse()->and($beta)->toMatchArray(['employees' => 2, 'fulfilled' => 1, 'avg_jp' => 11.0]);
})->group('jp');
