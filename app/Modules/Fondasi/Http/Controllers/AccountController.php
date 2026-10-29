<?php

namespace App\Modules\Fondasi\Http\Controllers;

use App\Modules\Fondasi\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/** Akun & setup 2FA (FON-06). Aksi 2FA memakai endpoint Fortify. */
class AccountController
{
    public function __invoke(Request $request): View
    {
        /** @var User $user */
        $user = $request->user()->load('employee', 'roles');

        return view('fondasi::account', [
            'user' => $user,
            'pendingConfirmation' => $user->two_factor_secret !== null && $user->two_factor_confirmed_at === null,
        ]);
    }
}
