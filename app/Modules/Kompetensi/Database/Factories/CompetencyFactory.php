<?php

namespace App\Modules\Kompetensi\Database\Factories;

use App\Modules\Kompetensi\Enums\CompetencyType;
use App\Modules\Kompetensi\Models\Competency;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Competency> */
class CompetencyFactory extends Factory
{
    protected $model = Competency::class;

    public function definition(): array
    {
        return [
            'code' => 'KOM-'.$this->faker->unique()->numerify('#####'),
            'name' => ucfirst($this->faker->unique()->words(3, true)),
            'type' => CompetencyType::Technical,
            'description' => $this->faker->sentence(),
            'level_descriptors' => ['1' => 'Dasar', '2' => 'Cakap', '3' => 'Mahir', '4' => 'Ahli', '5' => 'Pakar'],
            'is_active' => true,
        ];
    }
}
