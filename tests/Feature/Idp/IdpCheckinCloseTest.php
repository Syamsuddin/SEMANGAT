<?php

use App\Modules\Fondasi\Enums\Role;
use App\Modules\Idp\Actions\CloseIdp;
use App\Modules\Idp\Actions\RecordCheckin;
use App\Modules\Idp\Enums\IdpStatus;
use App\Modules\Idp\Livewire\IdpEditor;
use App\Modules\Idp\Livewire\TeamApprovalList;
use App\Modules\Integrasi\Models\Employee;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

beforeEach(function () {
    $this->atasan = userWithRole(Role::Atasan);
    $this->employee = Employee::query()->where('supervisor_id', $this->atasan->employee_id)->firstOrFail();
    $this->user = employeeUser($this->employee);
    $this->idp = idpWithItems($this->employee, 2, 'active');
});

it('IDP-06 check-in triwulanan tersimpan dengan progres per butir dan catatan atasan', function () {
    $this->travelTo(Carbon::create((int) now()->year, 5, 10));
    [$a, $b] = $this->idp->items->pluck('id')->all();

    Livewire::actingAs($this->user)->test(IdpEditor::class)
        ->set('progress', [$a => 40, $b => 25])
        ->set('checkinNote', 'Pelatihan selesai, mulai praktik.')
        ->call('saveCheckin')
        ->assertHasNoErrors()
        ->assertSee('Triwulan 2');

    $checkin = $this->idp->checkins()->sole();
    expect($checkin)->quarter->toBe(2)->progress->toBe([(string) $a => 40, (string) $b => 25]);

    Livewire::actingAs($this->atasan)->test(TeamApprovalList::class)
        ->set("checkinNotes.{$checkin->id}", 'Lanjutkan dengan proyek kecil.')
        ->call('reviewCheckin', $checkin->id)
        ->assertHasNoErrors();
    expect($checkin->refresh()->supervisor_note)->toBe('Lanjutkan dengan proyek kecil.');
})->group('idp');

it('IDP-06 check-in kedua pada triwulan yang sama ditolak; progres di luar 0–100 ditolak', function () {
    [$a, $b] = $this->idp->items->pluck('id')->all();
    app(RecordCheckin::class)->handle($this->user, $this->idp, 'Catatan pertama', [$a => 10, $b => 10]);

    expect(fn () => app(RecordCheckin::class)->handle($this->user, $this->idp, 'Catatan kedua', [$a => 20, $b => 20]))
        ->toThrow(ValidationException::class, 'sudah tercatat');
    $this->travel(4)->months();
    expect(fn () => app(RecordCheckin::class)->handle($this->user, $this->idp, 'Catatan', [$a => 120, $b => 0]))
        ->toThrow(ValidationException::class);
})->group('idp');

it('IDP-06 check-in hanya untuk IDP active milik sendiri', function () {
    $this->idp->update(['status' => IdpStatus::Approved]);

    expect(fn () => app(RecordCheckin::class)->handle($this->user, $this->idp->refresh(), 'Catatan', []))->toThrow(AuthorizationException::class);
    expect(fn () => app(RecordCheckin::class)->handle($this->atasan, $this->idp, 'Catatan', []))->toThrow(AuthorizationException::class);
})->group('idp');

it('IDP-07 ditutup dengan refleksi pegawai + penilaian atasan → status closed', function () {
    Livewire::actingAs($this->user)->test(IdpEditor::class)
        ->set('reflection', 'Saya kini menyusun analisis kebijakan sendiri.')
        ->call('saveReflection')
        ->assertHasNoErrors();

    Livewire::actingAs($this->atasan)->test(TeamApprovalList::class)
        ->assertSee('Saya kini menyusun analisis kebijakan sendiri.')
        ->set("assessments.{$this->idp->id}", 'Diterapkan pada dua kajian triwulan III.')
        ->call('close', $this->idp->id)
        ->assertHasNoErrors();

    expect($this->idp->refresh())
        ->status->toBe(IdpStatus::Closed)
        ->closing_reflection->toBe('Saya kini menyusun analisis kebijakan sendiri.')
        ->closing_assessment->toBe('Diterapkan pada dua kajian triwulan III.')
        ->closed_at->not->toBeNull();
    Livewire::actingAs($this->user)->test(IdpEditor::class)->assertSee('IDP ditutup');
})->group('idp');

it('IDP-07 atasan tidak dapat menutup sebelum refleksi; pegawai tidak dapat menutup sendiri', function () {
    expect(fn () => app(CloseIdp::class)->close($this->atasan, $this->idp, 'Penilaian'))
        ->toThrow(ValidationException::class, 'refleksi');

    app(CloseIdp::class)->reflect($this->user, $this->idp, 'Refleksi saya');
    expect(fn () => app(CloseIdp::class)->close($this->user, $this->idp->refresh(), 'Penilaian sendiri'))->toThrow(AuthorizationException::class);
})->group('idp');

it('IDP-07 pegawai mutasi: atasan baru yang menutup, atasan lama tidak lagi', function () {
    $newBoss = userWithRole(Role::Atasan);
    $this->employee->update(['supervisor_id' => $newBoss->employee_id]);
    app(CloseIdp::class)->reflect($this->user, $this->idp, 'Refleksi saya');

    expect(fn () => app(CloseIdp::class)->close($this->atasan, $this->idp->refresh(), 'Penilaian'))->toThrow(AuthorizationException::class);
    expect(app(CloseIdp::class)->close($newBoss, $this->idp->refresh(), 'Penilaian atasan baru')->status)->toBe(IdpStatus::Closed);
})->group('idp');
