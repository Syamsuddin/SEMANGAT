<?php

use App\Modules\Fondasi\Enums\Role;
use App\Modules\Fondasi\Models\AuditLog;
use App\Modules\Integrasi\Models\Employee;
use App\Modules\Kompetensi\Actions\RecalculateGaps;
use App\Modules\Kompetensi\Actions\ValidateSelfAssessment;
use App\Modules\Kompetensi\Events\AssessmentRecorded;
use App\Modules\Kompetensi\Listeners\RecalculateGapsOnAssessment;
use App\Modules\Kompetensi\Livewire\SelfAssessmentForm;
use App\Modules\Kompetensi\Livewire\SelfAssessmentValidation;
use App\Modules\Kompetensi\Models\Assessment;
use App\Modules\Kompetensi\Models\Competency;
use App\Modules\Kompetensi\Models\Gap;
use App\Support\Enums\Confidence;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Events\CallQueuedListener;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

beforeEach(function () {
    $this->competency = Competency::factory()->create();
    $this->supervisor = Employee::factory()->create();
    [$this->employee] = employeeWithSkj([$this->competency->id => 3], $this->supervisor);
    $this->asn = employeeUser($this->employee);
    $this->atasan = employeeUser($this->supervisor);
});

function submitSelfAssessment($test, int $level = 2): Assessment
{
    Livewire::actingAs($test->asn)->test(SelfAssessmentForm::class)
        ->set("levels.{$test->competency->id}", (string) $level)
        ->call('submit')
        ->assertHasNoErrors()
        ->assertRedirect(route('kompetensi.profile.show'));

    return Assessment::query()->where('employee_id', $test->employee->id)->sole();
}

it('KOM-06 ASN mengisi asesmen mandiri → menunggu validasi, tercatat di audit, belum memengaruhi gap', function () {
    $assessment = submitSelfAssessment($this);
    app(RecalculateGaps::class)($this->employee);

    expect($assessment->isPending())->toBeTrue()
        ->and($assessment->level)->toBe(2)
        ->and(AuditLog::query()->where('auditable_id', $assessment->id)->where('action', 'create')->exists())->toBeTrue()
        ->and(Gap::query()->where('employee_id', $this->employee->id)->sole()->confidence)->toBe(Confidence::Indicative);
})->group('kompetensi');

it('KOM-06 isian ulang selagi menunggu menimpa isian lama, tidak menggandakan', function () {
    submitSelfAssessment($this, 2);
    $assessment = submitSelfAssessment($this, 1);

    expect($assessment->level)->toBe(1);
})->group('kompetensi');

it('KOM-06 kompetensi di luar SKJ dan level di luar 1–5 ditolak', function () {
    $other = Competency::factory()->create();

    Livewire::actingAs($this->asn)->test(SelfAssessmentForm::class)
        ->set("levels.{$other->id}", '2')->set("levels.{$this->competency->id}", '7')
        ->call('submit')
        ->assertHasErrors(["levels.{$other->id}", "levels.{$this->competency->id}"]);

    expect(Assessment::query()->count())->toBe(0);
})->group('kompetensi');

it('KOM-06 atasan menolak → tidak dipakai mesin kesenjangan; alasan wajib & terlihat pegawai', function () {
    $assessment = submitSelfAssessment($this);
    $component = Livewire::actingAs($this->atasan)->test(SelfAssessmentValidation::class)
        ->assertSee($this->employee->name);

    $component->call('reject', $assessment->id)->assertHasErrors('note');
    $component->set("notes.{$assessment->id}", 'Belum sesuai bukti kerja')->call('reject', $assessment->id)->assertHasNoErrors();

    $assessment->refresh();
    app(RecalculateGaps::class)($this->employee);
    expect($assessment->rejected_at)->not->toBeNull()
        ->and($assessment->validated_by)->toBe($this->supervisor->id)
        ->and(Gap::query()->where('employee_id', $this->employee->id)->sole()->confidence)->toBe(Confidence::Indicative);
    $this->actingAs($this->asn)->get(route('kompetensi.profile.show'))->assertSee('Belum sesuai bukti kerja')->assertSee('Ditolak atasan');
})->group('kompetensi');

it('KOM-06 atasan menyetujui → listener hitung ulang masuk queue (≤ 1 menit), lalu gap berlabel Sementara', function () {
    $assessment = submitSelfAssessment($this);
    Queue::fake();

    Livewire::actingAs($this->atasan)->test(SelfAssessmentValidation::class)->call('approve', $assessment->id)->assertHasNoErrors();

    Queue::assertPushed(CallQueuedListener::class, fn ($job) => $job->class === RecalculateGapsOnAssessment::class);
    expect($assessment->refresh()->validated_at)->not->toBeNull();

    app(RecalculateGapsOnAssessment::class)->handle(new AssessmentRecorded($this->employee->id));
    $gap = Gap::query()->where('employee_id', $this->employee->id)->sole();
    expect($gap->gap)->toBe(1)->and($gap->confidence)->toBe(Confidence::Provisional)->and($gap->assessment_id)->toBe($assessment->id);
})->group('kompetensi');

it('KOM-06 hanya atasan langsung yang dapat memvalidasi; asesmen yang sudah diputus tidak bisa divalidasi ulang', function () {
    $assessment = submitSelfAssessment($this);
    $otherAtasan = userWithRole(Role::Atasan);
    $validate = app(ValidateSelfAssessment::class);

    expect(fn () => $validate->approve($otherAtasan, $assessment))->toThrow(AuthorizationException::class)
        ->and(fn () => $validate->approve($this->asn, $assessment))->toThrow(AuthorizationException::class);

    $validate->approve($this->atasan, $assessment);
    expect(fn () => $validate->reject($this->atasan, $assessment->refresh(), 'alasan panjang'))->toThrow(AuthorizationException::class);
})->group('kompetensi');

it('KOM-06 daftar validasi atasan tidak memuat asesmen bawahan atasan lain', function () {
    submitSelfAssessment($this);
    $otherAtasan = userWithRole(Role::Atasan);

    Livewire::actingAs($otherAtasan)->test(SelfAssessmentValidation::class)
        ->assertDontSee($this->employee->name)
        ->assertSee('Tidak ada asesmen yang menunggu');
})->group('kompetensi');
