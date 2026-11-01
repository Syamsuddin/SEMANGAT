<?php

namespace App\Modules\Idp\Database\Factories;

use App\Modules\Idp\Enums\IdpStatus;
use App\Modules\Idp\Models\Idp;
use App\Modules\Integrasi\Models\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Idp> */
class IdpFactory extends Factory
{
    protected $model = Idp::class;

    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'period_year' => (int) now()->year,
            'status' => IdpStatus::Draft,
            'target_jp' => 20,
        ];
    }

    public function submitted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => IdpStatus::Submitted,
            'submitted_at' => now(),
            'supervisor_id' => fn (array $a) => Employee::query()->whereKey($a['employee_id'])->value('supervisor_id'),
        ]);
    }

    public function approved(): static
    {
        return $this->submitted()->state(fn () => ['status' => IdpStatus::Approved, 'approved_at' => now()]);
    }

    public function active(): static
    {
        return $this->approved()->state(fn () => ['status' => IdpStatus::Active]);
    }
}
