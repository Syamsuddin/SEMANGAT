<?php

namespace App\Modules\Kompetensi\Database\Factories;

use App\Modules\Integrasi\Models\Position;
use App\Modules\Kompetensi\Models\Competency;
use App\Modules\Kompetensi\Models\JobStandard;
use App\Modules\Kompetensi\Models\JobStandardItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<JobStandard> */
class JobStandardFactory extends Factory
{
    protected $model = JobStandard::class;

    public function definition(): array
    {
        return [
            'position_code' => 'JAB-'.$this->faker->unique()->numerify('#####'),
            'name' => 'SKJ '.$this->faker->words(2, true),
            'effective_on' => now()->subYear()->toDateString(),
        ];
    }

    public function forPosition(Position $position): static
    {
        return $this->state(fn () => ['position_code' => $position->code, 'name' => 'SKJ '.$position->name]);
    }

    /** @param array<int, int> $levels competency_id => required_level */
    public function requiring(array $levels): static
    {
        return $this->afterCreating(function (JobStandard $standard) use ($levels) {
            foreach ($levels as $competencyId => $level) {
                JobStandardItem::factory()->create([
                    'job_standard_id' => $standard->id,
                    'competency_id' => $competencyId,
                    'required_level' => $level,
                ]);
            }
        });
    }

    /** Buat n kompetensi baru masing-masing dengan level dipersyaratkan $level. */
    public function withCompetencies(int $count, int $level = 3): static
    {
        return $this->afterCreating(function (JobStandard $standard) use ($count, $level) {
            foreach (Competency::factory()->count($count)->create() as $competency) {
                JobStandardItem::factory()->create([
                    'job_standard_id' => $standard->id,
                    'competency_id' => $competency->id,
                    'required_level' => $level,
                ]);
            }
        });
    }
}
