<?php

namespace App\Modules\Integrasi\Database\Factories;

use App\Modules\Integrasi\Enums\PositionTier;
use App\Modules\Integrasi\Enums\PositionType;
use App\Modules\Integrasi\Models\Position;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Position> */
class PositionFactory extends Factory
{
    protected $model = Position::class;

    public function definition(): array
    {
        $code = 'JAB-'.$this->faker->unique()->numerify('#####');

        return [
            'code' => $code,
            'name' => 'Analis '.$this->faker->words(2, true),
            'type' => PositionType::Functional,
            'tier' => PositionTier::Fungsional,
            'source' => 'employee_file',
            'external_id' => $code,
            'synced_at' => now(),
        ];
    }
}
