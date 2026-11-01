<?php

namespace App\Modules\Kompetensi\Database\Factories;

use App\Modules\Integrasi\Models\Employee;
use App\Modules\Kompetensi\Enums\AssessmentMethod;
use App\Modules\Kompetensi\Models\Assessment;
use App\Modules\Kompetensi\Models\Competency;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Assessment> Default: hasil assessment center terverifikasi, berlaku. */
class AssessmentFactory extends Factory
{
    protected $model = Assessment::class;

    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'competency_id' => Competency::factory(),
            'level' => 2,
            'method' => AssessmentMethod::AssessmentCenter,
            'confidence' => AssessmentMethod::AssessmentCenter->confidence(),
            'institution' => 'UPT Penilaian Kompetensi',
            'assessed_on' => now()->subMonths(6)->toDateString(),
            'valid_until' => now()->addYears(2)->toDateString(),
        ];
    }

    public function expired(): static
    {
        return $this->state(fn () => [
            'assessed_on' => now()->subYears(4)->toDateString(),
            'valid_until' => now()->subDay()->toDateString(),
        ]);
    }

    /** Asesmen mandiri menunggu validasi atasan. */
    public function selfAssessment(): static
    {
        return $this->state(fn () => [
            'method' => AssessmentMethod::SelfAssessment,
            'confidence' => AssessmentMethod::SelfAssessment->confidence(),
            'institution' => null,
            'assessed_on' => now()->toDateString(),
            'valid_until' => null,
        ]);
    }

    public function validatedBy(Employee $supervisor): static
    {
        return $this->selfAssessment()->state(fn () => ['validated_by' => $supervisor->id, 'validated_at' => now()]);
    }

    public function inferredFromSkp(): static
    {
        return $this->state(fn () => [
            'method' => AssessmentMethod::InferredSkp,
            'confidence' => AssessmentMethod::InferredSkp->confidence(),
            'institution' => null,
            'valid_until' => null,
        ]);
    }
}
