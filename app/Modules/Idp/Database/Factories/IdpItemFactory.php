<?php

namespace App\Modules\Idp\Database\Factories;

use App\Modules\Idp\Models\Idp;
use App\Modules\Idp\Models\IdpItem;
use App\Modules\Kompetensi\Models\Competency;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<IdpItem> */
class IdpItemFactory extends Factory
{
    protected $model = IdpItem::class;

    public function definition(): array
    {
        return [
            'idp_id' => Idp::factory(),
            'competency_id' => Competency::factory(),
            'start_level' => 1,
            'target_level' => 3,
            'position' => 1,
        ];
    }
}
