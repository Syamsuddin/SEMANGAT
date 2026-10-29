<?php

namespace App\Modules\Fondasi\Database\Factories;

use App\Modules\Fondasi\Models\User;
use App\Modules\Integrasi\Models\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/** @extends Factory<User> */
class UserFactory extends Factory
{
    protected $model = User::class;

    protected static ?string $password = null;

    public function definition(): array
    {
        return [
            'username' => 'tek-'.$this->faker->unique()->numerify('######'),
            'password' => static::$password ??= Hash::make('kata-sandi-uji-123'),
            'is_active' => true,
        ];
    }

    public function forEmployee(Employee $employee): static
    {
        return $this->state(fn () => [
            'employee_id' => $employee->id,
            'username' => $employee->nip,
            'is_active' => $employee->isActive(),
        ]);
    }

    public function withConfirmedTwoFactor(): static
    {
        return $this->state(fn () => [
            'two_factor_secret' => encrypt('JBSWY3DPEHPK3PXP'),
            'two_factor_recovery_codes' => encrypt(json_encode(['kode-1', 'kode-2'])),
            'two_factor_confirmed_at' => now(),
        ]);
    }
}
