<?php

namespace App\Modules\Integrasi\Database\Factories;

use App\Modules\Integrasi\Enums\OrgUnitLevel;
use App\Modules\Integrasi\Models\OrgUnit;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<OrgUnit> */
class OrgUnitFactory extends Factory
{
    protected $model = OrgUnit::class;

    public function definition(): array
    {
        $code = 'OPD-'.$this->faker->unique()->numerify('####');

        return [
            'code' => $code,
            'name' => 'Dinas '.$this->faker->unique()->words(2, true),
            'level' => OrgUnitLevel::Opd,
            'source' => 'employee_file',
            'external_id' => $code,
            'synced_at' => now(),
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (OrgUnit $unit) {
            if ($unit->level === OrgUnitLevel::Opd && $unit->opd_id === null) {
                $unit->forceFill(['opd_id' => $unit->id])->save();
            }
        });
    }

    public function division(OrgUnit $opd): static
    {
        return $this->state(fn () => [
            'level' => OrgUnitLevel::Division,
            'name' => 'Bidang '.$this->faker->unique()->words(2, true),
            'parent_id' => $opd->id,
            'opd_id' => $opd->id,
        ]);
    }
}
