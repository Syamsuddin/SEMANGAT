<?php

namespace App\Modules\Kompetensi\Database\Factories;

use App\Modules\Integrasi\Models\Employee;
use App\Modules\Kompetensi\Models\Competency;
use App\Modules\Kompetensi\Models\Gap;
use App\Support\Enums\Confidence;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Gap> */
class GapFactory extends Factory
{
    protected $model = Gap::class;

    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'competency_id' => Competency::factory(),
            'required_level' => 3,
            'actual_level' => null,
            'gap' => 3,
            'confidence' => Confidence::Indicative,
            'calculated_at' => now(),
        ];
    }
}
