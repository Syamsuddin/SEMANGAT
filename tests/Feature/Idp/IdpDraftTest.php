<?php

use App\Modules\Idp\Actions\StartIdp;
use App\Modules\Idp\Enums\IdpStatus;
use App\Modules\Idp\Livewire\IdpEditor;
use App\Modules\Idp\Models\Idp;
use App\Modules\Integrasi\Models\Employee;
use App\Modules\Kompetensi\Models\Competency;
use App\Modules\Kompetensi\Models\Gap;
use Livewire\Livewire;

it('IDP-01 draf berisi ≤3 kompetensi gap terbesar dengan usulan tautan RHK', function () {
    $employee = Employee::factory()->create();
    $user = employeeUser($employee);
    $names = ['Analisis Data' => 3, 'Pengelolaan Anggaran' => 2, 'Komunikasi Publik' => 4, 'Integritas' => 1];
    foreach ($names as $name => $gap) {
        Gap::factory()->create([
            'employee_id' => $employee->id, 'competency_id' => Competency::factory()->create(['name' => $name])->id,
            'required_level' => 4, 'actual_level' => 4 - $gap, 'gap' => $gap,
        ]);
    }
    [$rhkData, $rhkPublik] = skpWorkResults($employee, ['Tersedianya analisis data kepegawaian', 'Terlaksananya komunikasi publik layanan']);

    $idp = app(StartIdp::class)->handle($user);

    expect($idp->status)->toBe(IdpStatus::Draft)
        ->and($idp->period_year)->toBe((int) now()->year)
        ->and($idp->items()->count())->toBe(3);
    $items = $idp->items()->with('competency')->get();
    expect($items->pluck('competency.name')->all())->toBe(['Komunikasi Publik', 'Analisis Data', 'Pengelolaan Anggaran'])
        ->and($items[0]->skp_work_result_id)->toBe($rhkPublik)
        ->and($items[1]->skp_work_result_id)->toBe($rhkData)
        ->and($items[2]->skp_work_result_id)->not->toBeNull()
        ->and($items[0]->start_level)->toBe(0)
        ->and($items[0]->target_level)->toBe(4);
})->group('idp');

it('IDP-01 membuka "Susun IDP" kedua kali mengembalikan draf yang sama', function () {
    $user = employeeUser();

    $first = app(StartIdp::class)->handle($user);
    $second = app(StartIdp::class)->handle($user);

    expect($second->id)->toBe($first->id)->and(Idp::query()->count())->toBe(1);
})->group('idp');

it('IDP-01 halaman IDP saya: kosong → Susun IDP → draf tampil', function () {
    $employee = Employee::factory()->create();
    $user = employeeUser($employee);
    Gap::factory()->create(['employee_id' => $employee->id, 'competency_id' => Competency::factory()->create(['name' => 'Analisis Data'])->id]);

    $this->actingAs($user)->get(route('idp.plans.mine'))->assertOk()->assertSee('Belum ada IDP untuk periode ini.')->assertSee('Susun IDP');

    Livewire::actingAs($user)->test(IdpEditor::class)
        ->call('start')
        ->assertDispatched('toast')
        ->assertSee('Analisis Data')
        ->assertSee('Belum tertaut SKP');
})->group('idp');

it('IDP-01 IDP periode yang dibatalkan tidak menghalangi IDP baru periode sama', function () {
    $employee = Employee::factory()->create();
    Idp::factory()->for($employee, 'employee')->create(['status' => IdpStatus::Cancelled]);

    $idp = app(StartIdp::class)->handle(employeeUser($employee));

    expect($idp->status)->toBe(IdpStatus::Draft)->and(Idp::query()->count())->toBe(2);
})->group('idp');

it('IDP-03 aspirasi karier dan target JP tersimpan saat draf', function () {
    $employee = Employee::factory()->create();
    $user = employeeUser($employee);
    app(StartIdp::class)->handle($user);

    Livewire::actingAs($user)->test(IdpEditor::class)
        ->set('form.career_aspiration', 'Menjadi analis kebijakan ahli muda')
        ->set('form.target_jp', 24)
        ->call('saveIdp')
        ->assertHasNoErrors();

    expect(Idp::query()->first())->career_aspiration->toBe('Menjadi analis kebijakan ahli muda')->target_jp->toBe(24);
})->group('idp');

it('IDP-02 butir & aktivitas 10:20:70 dapat ditambah dari halaman', function () {
    $employee = Employee::factory()->create();
    $user = employeeUser($employee);
    $rhk = skpWorkResults($employee, ['Laporan analisis'])[0];
    app(StartIdp::class)->handle($user);
    $competency = Competency::factory()->create(['name' => 'Penulisan Laporan']);

    $component = Livewire::actingAs($user)->test(IdpEditor::class)
        ->set('newItem', ['competency_id' => $competency->id, 'start_level' => 2, 'target_level' => 3, 'skp_work_result_id' => $rhk])
        ->call('addItem')
        ->assertHasNoErrors();
    $item = Idp::query()->firstOrFail()->items()->firstOrFail();

    $component->set("activity.{$item->id}", ['portion' => 70, 'title' => 'Penugasan menyusun laporan triwulan', 'planned_jp' => 6])
        ->call('addActivity', $item->id)
        ->assertHasNoErrors()
        ->assertSee('Penugasan menyusun laporan triwulan');

    expect($item->activities()->first())->portion->value->toBe(70)->planned_jp->toBe(6);
})->group('idp');

it('IDP-02 butir ke-4 dari halaman menampilkan pesan maksimal 3', function () {
    $employee = Employee::factory()->create();
    $user = employeeUser($employee);
    idpWithItems($employee, 3);

    Livewire::actingAs($user)->test(IdpEditor::class)
        ->set('newItem', ['competency_id' => Competency::factory()->create()->id, 'start_level' => 1, 'target_level' => 2])
        ->call('addItem')
        ->assertHasErrors('items');
})->group('idp');
