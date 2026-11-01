<?php

use App\Modules\Kompetensi\Enums\AssessmentMethod;
use App\Modules\Kompetensi\Models\Assessment;
use App\Modules\Kompetensi\Services\GapCalculator;
use App\Support\Enums\Confidence;

// docs/13: GapCalculator 100% cabang — prioritas keyakinan, kedaluwarsa, asesmen mandiri menunggu/ditolak.

function assessment(int $competencyId, int $level, AssessmentMethod $method, array $attrs = []): Assessment
{
    static $id = 0;
    $a = new Assessment(['competency_id' => $competencyId, 'level' => $level, 'method' => $method,
        'confidence' => $method->confidence(), 'assessed_on' => now()->subMonth()->toDateString()] + $attrs);
    $a->id = ++$id;

    return $a;
}

beforeEach(fn () => $this->calc = new GapCalculator);

it('KOM-04/05 hasil uji verified level 2 berlaku vs SKJ level 3 → gap 1 Terverifikasi', function () {
    [$line] = $this->calc->calculate([10 => 3], [assessment(10, 2, AssessmentMethod::AssessmentCenter, ['valid_until' => now()->addYear()->toDateString()])]);

    expect($line->gap)->toBe(1)->and($line->actualLevel)->toBe(2)->and($line->confidence)->toBe(Confidence::Verified);
})->group('kompetensi');

it('KOM-04/05 hanya asesmen mandiri tervalidasi level 2 → gap 1 Sementara', function () {
    [$line] = $this->calc->calculate([10 => 3], [assessment(10, 2, AssessmentMethod::SelfAssessment, ['validated_at' => now()])]);

    expect($line->gap)->toBe(1)->and($line->confidence)->toBe(Confidence::Provisional);
})->group('kompetensi');

it('KOM-04/05 tanpa data → gap = level dipersyaratkan, Indikatif', function () {
    [$line] = $this->calc->calculate([10 => 3], []);

    expect($line->gap)->toBe(3)->and($line->actualLevel)->toBeNull()->and($line->confidence)->toBe(Confidence::Indicative)
        ->and($line->assessmentId)->toBeNull();
})->group('kompetensi');

it('KOM-03 verified kedaluwarsa + asesmen mandiri tervalidasi lebih baru → level aktual dari asesmen mandiri (provisional)', function () {
    [$line] = $this->calc->calculate([10 => 3], [
        assessment(10, 3, AssessmentMethod::AssessmentCenter, ['assessed_on' => now()->subYears(4)->toDateString(), 'valid_until' => now()->subDay()->toDateString()]),
        $self = assessment(10, 2, AssessmentMethod::SelfAssessment, ['validated_at' => now()]),
    ]);

    expect($line->actualLevel)->toBe(2)->and($line->confidence)->toBe(Confidence::Provisional)->and($line->assessmentId)->toBe($self->id);
})->group('kompetensi');

it('KOM-03 verified berlaku mengalahkan asesmen mandiri yang lebih baru', function () {
    [$line] = $this->calc->calculate([10 => 4], [
        $verified = assessment(10, 2, AssessmentMethod::JfCompetencyTest, ['assessed_on' => now()->subYear()->toDateString()]),
        assessment(10, 4, AssessmentMethod::SelfAssessment, ['validated_at' => now(), 'assessed_on' => now()->toDateString()]),
    ]);

    expect($line->assessmentId)->toBe($verified->id)->and($line->gap)->toBe(2)->and($line->confidence)->toBe(Confidence::Verified);
})->group('kompetensi');

it('KOM-03 di antara keyakinan sama, tanggal terbaru menang', function () {
    [$line] = $this->calc->calculate([10 => 3], [
        assessment(10, 1, AssessmentMethod::AssessmentCenter, ['assessed_on' => '2024-01-01']),
        $newer = assessment(10, 2, AssessmentMethod::AssessmentCenter, ['assessed_on' => '2025-06-01']),
    ]);

    expect($line->assessmentId)->toBe($newer->id)->and($line->gap)->toBe(1);
})->group('kompetensi');

it('KOM-06 asesmen mandiri menunggu atau ditolak tidak dipakai', function () {
    $lines = $this->calc->calculate([10 => 3, 11 => 3], [
        assessment(10, 3, AssessmentMethod::SelfAssessment),
        assessment(11, 3, AssessmentMethod::SelfAssessment, ['validated_at' => now(), 'rejected_at' => now()]),
    ]);

    expect(collect($lines)->every(fn ($l) => $l->gap === 3 && $l->confidence === Confidence::Indicative))->toBeTrue();
})->group('kompetensi');

it('KOM-05 disimpulkan dari SKP → Indikatif', function () {
    [$line] = $this->calc->calculate([10 => 3], [assessment(10, 2, AssessmentMethod::InferredSkp)]);

    expect($line->confidence)->toBe(Confidence::Indicative)->and($line->gap)->toBe(1);
})->group('kompetensi');

it('KOM-04 level aktual ≥ dipersyaratkan → gap 0; hasil uji kompetensi di luar SKJ diabaikan; urut gap terbesar', function () {
    $lines = $this->calc->calculate([10 => 2, 11 => 4], [
        assessment(10, 3, AssessmentMethod::AssessmentCenter),
        assessment(99, 1, AssessmentMethod::AssessmentCenter),
    ]);

    expect(array_map(fn ($l) => [$l->competencyId, $l->gap], $lines))->toBe([[11, 4], [10, 0]])
        ->and($lines[1]->hasGap())->toBeFalse();
})->group('kompetensi');
