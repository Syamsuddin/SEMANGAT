<?php

namespace App\Modules\Fondasi\Actions;

use App\Modules\Fondasi\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * FON-01: login NIP + kata sandi. Status aktif diperiksa setelah kata sandi cocok
 * agar keberadaan akun tidak bocor ke penebak. Tanpa efek samping: Fortify dapat memanggilnya
 * lebih dari sekali per percobaan (pipeline 2FA) — pencatatan login di RecordSuccessfulLogin.
 */
class AuthenticateUser
{
    public function __invoke(string $username, string $password): ?User
    {
        $user = User::query()->with('employee')->where('username', $username)->first();

        if ($user === null || ! Hash::check($password, $user->password)) {
            return null;
        }

        if (! $user->is_active || ($user->employee !== null && ! $user->employee->isActive())) {
            throw ValidationException::withMessages(['username' => __('fondasi.auth.inactive')]);
        }

        return $user;
    }
}
