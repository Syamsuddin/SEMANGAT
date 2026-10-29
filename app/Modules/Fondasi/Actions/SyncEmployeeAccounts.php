<?php

namespace App\Modules\Fondasi\Actions;

use App\Modules\Fondasi\Models\User;
use App\Modules\Integrasi\Models\Employee;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Setelah sinkron: pastikan setiap pegawai punya akun (username = NIP), status akun mengikuti
 * status pegawai, lalu hitung ulang peran turunan. Akun baru diberi kata sandi acak yang tidak
 * diketahui siapa pun — aktivasi akun ditetapkan terpisah [TERBUKA].
 */
class SyncEmployeeAccounts
{
    public function __construct(private readonly SyncUserRoles $syncRoles) {}

    public function __invoke(): void
    {
        Employee::query()->with('user')->chunkById(500, function ($employees) {
            DB::transaction(function () use ($employees) {
                foreach ($employees as $employee) {
                    $user = $employee->user ?? new User([
                        'employee_id' => $employee->id,
                        'username' => $employee->nip,
                        'password' => Hash::make(Str::random(40)),
                    ]);
                    $user->is_active = $employee->isActive();
                    $user->save();
                }
            });
        });

        $this->syncRoles->forAll();
    }
}
