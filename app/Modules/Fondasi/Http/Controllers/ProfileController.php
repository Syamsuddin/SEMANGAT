<?php

namespace App\Modules\Fondasi\Http\Controllers;

use App\Modules\Fondasi\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** "Profil saya" = halaman pegawai milik sendiri. */
class ProfileController
{
    public function __invoke(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        abort_if($user->employee_id === null, 404);

        return redirect()->route('fondasi.employees.show', $user->employee_id);
    }
}
