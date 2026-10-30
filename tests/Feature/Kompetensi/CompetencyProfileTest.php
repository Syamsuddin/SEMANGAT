<?php

use App\Modules\Integrasi\Models\Employee;
use App\Modules\Kompetensi\Actions\RecalculateGaps;
use App\Modules\Kompetensi\Models\Assessment;
use App\Modules\Kompetensi\Models\Competency;
use App\Modules\Kompetensi\Models\Gap;
use App\Support\Enums\Confidence;
use Illuminate\Support\Facades\Artisan;

it('KOM-04/05 profil menampilkan gap Terverifikasi, Sementara, dan Indikatif sesuai sumber data', function () {
    [$verified, $self, $none] = Competency::factory()->count(3)->create();
    $supervisor = Employee::factory()->create();
    [$employee] = employeeWithSkj([$verified->id => 3, $self->id => 3, $none->id => 3], $supervisor);
    Assessment::factory()->create(['employee_id' => $employee->id, 'competency_id' => $verified->id, 'level' => 2]);
    Assessment::factory()->validatedBy($supervisor)->create(['employee_id' => $employee->id, 'competency_id' => $self->id, 'level' => 2]);
    $user = employeeUser($employee);

    $html = $this->actingAs($user)->get(route('kompetensi.profile.show'))->assertOk()->getContent();

    expect(substr_count($html, 'data-confidence="verified"'))->toBeGreaterThanOrEqual(1)
        ->and($html)->toContain('data-confidence="provisional"')
        ->and($html)->toContain('data-confidence="indicative"')
        ->and($html)->toContain('Kurang 1 level')
        ->and($html)->toContain('Kurang 3 level');
})->group('kompetensi');

it('KOM-05 sentinel: gap indikatif memakai komponen badge indicative (garis titik-titik), bukan gaya verified', function () {
    [$employee] = employeeWithSkj();
    $html = $this->actingAs(employeeUser($employee))->get(route('kompetensi.profile.show'))->assertOk()->getContent();

    preg_match_all('/<span[^>]*data-confidence="([a-z]+)"[^>]*>/', $html, $badges, PREG_SET_ORDER);
    expect($badges)->not->toBeEmpty();
    foreach ($badges as [$tag, $status]) {
        expect($status)->toBe('indicative')
            ->and($tag)->toContain('border-dotted')
            ->and($tag)->not->toContain('bg-teal-50')
            ->and($tag)->not->toContain('border-solid');
    }
    // Level aktual tanpa data tidak boleh bergaya angka tebal terverifikasi.
    expect($html)->not->toContain('font-bold text-navy');
})->group('kompetensi');

it('KOM-04 RecalculateGaps menyimpan hanya gap > 0 dan menghapus gap yang sudah tertutup', function () {
    [$a, $b] = Competency::factory()->count(2)->create();
    [$employee] = employeeWithSkj([$a->id => 3, $b->id => 2]);
    app(RecalculateGaps::class)($employee);
    expect(Gap::query()->where('employee_id', $employee->id)->count())->toBe(2);

    Assessment::factory()->create(['employee_id' => $employee->id, 'competency_id' => $b->id, 'level' => 2]);
    Assessment::factory()->create(['employee_id' => $employee->id, 'competency_id' => $a->id, 'level' => 1]);
    app(RecalculateGaps::class)($employee);

    $gaps = Gap::query()->where('employee_id', $employee->id)->get();
    expect($gaps)->toHaveCount(1)
        ->and($gaps[0]->competency_id)->toBe($a->id)
        ->and($gaps[0]->gap)->toBe(2)
        ->and($gaps[0]->actual_level)->toBe(1)
        ->and($gaps[0]->confidence)->toBe(Confidence::Verified);
})->group('kompetensi');

it('KOM-04 pegawai nonaktif atau tanpa SKJ tidak punya kesenjangan tersimpan', function () {
    [$employee] = employeeWithSkj();
    app(RecalculateGaps::class)($employee);
    expect(Gap::query()->where('employee_id', $employee->id)->count())->toBe(3);

    $employee->forceFill(['status' => 'inactive'])->save();
    app(RecalculateGaps::class)($employee->refresh());

    expect(Gap::query()->where('employee_id', $employee->id)->count())->toBe(0);
})->group('kompetensi');

it('KOM-01 SKJ dicocokkan lewat kode jabatan; yang belum berlaku diabaikan; tanpa SKJ tampil keadaan kosong', function () {
    [$employee, $standard] = employeeWithSkj();
    $standard->forceFill(['effective_on' => now()->addMonth()->toDateString()])->save();

    $this->actingAs(employeeUser($employee))->get(route('kompetensi.profile.show'))
        ->assertOk()->assertSee('SKJ jabatan belum tersedia');
})->group('kompetensi');

it('KOM-04 semangat:gaps:recalculate --employee menghitung satu pegawai', function () {
    [$employee] = employeeWithSkj();

    expect(Artisan::call('semangat:gaps:recalculate', ['--employee' => $employee->nip]))->toBe(0)
        ->and(Gap::query()->where('employee_id', $employee->id)->count())->toBe(3)
        ->and(Artisan::call('semangat:gaps:recalculate', ['--employee' => '199900000000000000']))->toBe(1);
})->group('kompetensi');
