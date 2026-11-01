<?php

namespace App\Modules\JpRiwayat\Database\Factories;

use App\Modules\Integrasi\Models\Employee;
use App\Modules\JpRiwayat\Enums\JpStatus;
use App\Modules\JpRiwayat\Models\JpRecord;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<JpRecord> */
class JpRecordFactory extends Factory
{
    protected $model = JpRecord::class;

    public function definition(): array
    {
        $year = (int) now()->year;

        return [
            'employee_id' => Employee::factory(),
            'year' => $year,
            'title' => 'Pelatihan '.$this->faker->words(3, true),
            'portion' => 10,
            'mode' => 'classical',
            'jp' => 4,
            'activity_start' => $year.'-03-01',
            'activity_end' => $year.'-03-02',
            'status' => JpStatus::Pending,
        ];
    }

    public function verified(): static
    {
        return $this->state(fn () => ['status' => JpStatus::Verified, 'verified_at' => now()]);
    }

    public function rejected(string $reason = 'Sertifikat tidak terbaca'): static
    {
        return $this->state(fn () => ['status' => JpStatus::Rejected, 'verified_at' => now(), 'reject_reason' => $reason]);
    }
}
