<?php

use App\Modules\Idp\Actions\SaveIdpItem;
use App\Modules\Idp\Actions\SubmitIdp;
use App\Modules\Idp\Enums\IdpStatus;
use App\Modules\Integrasi\Models\Employee;
use App\Modules\Kompetensi\Models\Competency;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    Notification::fake();
    $this->supervisor = Employee::factory()->create();
    $this->employee = Employee::factory()->supervisedBy($this->supervisor)->create();
    $this->user = employeeUser($this->employee);
});

it('IDP-02 butir ke-4 ditolak "IDP maksimal 3 kompetensi"', function () {
    $idp = idpWithItems($this->employee, 3);
    $competency = Competency::factory()->create();

    expect(fn () => app(SaveIdpItem::class)->handle($this->user, $idp, ['competency_id' => $competency->id, 'start_level' => 1, 'target_level' => 2]))
        ->toThrow(ValidationException::class, 'IDP maksimal 3 kompetensi');
    expect($idp->items()->count())->toBe(3);
})->group('idp');

it('IDP-02 ajukan dengan 1 butir ditolak "Pilih sedikitnya 2 kompetensi prioritas"', function () {
    $idp = idpWithItems($this->employee, 1);

    expect(fn () => app(SubmitIdp::class)->handle($this->user, $idp))
        ->toThrow(ValidationException::class, 'Pilih sedikitnya 2 kompetensi prioritas');
    expect($idp->refresh()->status)->toBe(IdpStatus::Draft);
})->group('idp');

it('IDP-02 dua dan tiga butir dapat diajukan', function (int $items) {
    $idp = idpWithItems($this->employee, $items);

    expect(app(SubmitIdp::class)->handle($this->user, $idp)->status)->toBe(IdpStatus::Submitted);
})->with([2, 3])->group('idp');

it('IDP-02 level target harus di atas level awal dan kompetensi tidak boleh ganda', function () {
    $idp = idpWithItems($this->employee, 2);
    $existing = $idp->items->first();

    expect(fn () => app(SaveIdpItem::class)->handle($this->user, $idp, ['competency_id' => Competency::factory()->create()->id, 'start_level' => 3, 'target_level' => 3]))
        ->toThrow(ValidationException::class);
    expect(fn () => app(SaveIdpItem::class)->handle($this->user, $idp, ['competency_id' => $existing->competency_id, 'start_level' => 1, 'target_level' => 2]))
        ->toThrow(ValidationException::class);
})->group('idp');

it('IDP-02 tautan RHK hanya ke RHK SKP milik pegawai pada periode IDP', function () {
    $idp = idpWithItems($this->employee, 2);
    $foreign = skpWorkResults(Employee::factory()->create(), ['RHK milik orang lain'])[0];

    expect(fn () => app(SaveIdpItem::class)->handle($this->user, $idp, ['competency_id' => Competency::factory()->create()->id, 'start_level' => 1, 'target_level' => 2, 'skp_work_result_id' => $foreign]))
        ->toThrow(ValidationException::class);
})->group('idp');

it('IDP-02 transisi: IDP selain draf tidak dapat diubah atau diajukan ulang', function (string $state) {
    $idp = idpWithItems($this->employee, 2, $state);

    expect(fn () => app(SubmitIdp::class)->handle($this->user, $idp))->toThrow(AuthorizationException::class);
    expect(fn () => app(SaveIdpItem::class)->handle($this->user, $idp, ['competency_id' => Competency::factory()->create()->id, 'start_level' => 1, 'target_level' => 2]))
        ->toThrow(AuthorizationException::class);
})->with(['submitted', 'approved', 'active'])->group('idp');
