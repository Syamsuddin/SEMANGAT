<?php

namespace App\Modules\Kompetensi\Database\Factories;

use App\Modules\Kompetensi\Models\Competency;
use App\Modules\Kompetensi\Models\JobStandard;
use App\Modules\Kompetensi\Models\JobStandardItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<JobStandardItem> */
class JobStandardItemFactory extends Factory
{
    protected $model = JobStandardItem::class;

    public function definition(): array
    {
        return [
            'job_standard_id' => JobStandard::factory(),
            'competency_id' => Competency::factory(),
            'required_level' => 3,
        ];
    }
}
