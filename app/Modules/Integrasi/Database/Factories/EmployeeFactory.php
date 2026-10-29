<?php

namespace App\Modules\Integrasi\Database\Factories;

use App\Modules\Integrasi\Enums\EmployeeStatus;
use App\Modules\Integrasi\Enums\EmploymentType;
use App\Modules\Integrasi\Models\Employee;
use App\Modules\Integrasi\Models\OrgUnit;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Employee> NIP fiktif berawalan 1999 (docs/10). */
class EmployeeFactory extends Factory
{
    protected $model = Employee::class;

    public function definition(): array
    {
        $nip = '1999'.$this->faker->unique()->numerify('##############');

        return [
            'nip' => $nip,
            'name' => $this->faker->name(),
            'employment_type' => EmploymentType::Pns,
            'status' => EmployeeStatus::Active,
            'org_unit_id' => OrgUnit::factory(),
            'opd_id' => fn (array $attrs) => OrgUnit::query()->findOrFail($attrs['org_unit_id'])->opd_id ?? $attrs['org_unit_id'],
            'source' => 'employee_file',
            'external_id' => $nip,
            'synced_at' => now(),
        ];
    }

    public function inOpd(OrgUnit $opd): static
    {
        return $this->state(fn () => ['org_unit_id' => $opd->id, 'opd_id' => $opd->id]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['status' => EmployeeStatus::Inactive]);
    }

    public function supervisedBy(Employee $supervisor): static
    {
        return $this->state(fn () => [
            'supervisor_id' => $supervisor->id,
            'org_unit_id' => $supervisor->org_unit_id,
            'opd_id' => $supervisor->opd_id,
        ]);
    }
}
