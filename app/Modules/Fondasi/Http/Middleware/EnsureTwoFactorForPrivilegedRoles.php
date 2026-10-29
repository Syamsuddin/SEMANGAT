<?php

namespace App\Modules\Fondasi\Http\Middleware;

use App\Modules\Fondasi\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** FON-06: peran 2FA tanpa 2FA terkonfirmasi hanya boleh membuka halaman setup 2FA. */
class EnsureTwoFactorForPrivilegedRoles
{
    /** Rute yang dibutuhkan untuk menyelesaikan setup 2FA. */
    private const ALLOWED = [
        'fondasi.account', 'two-factor.*', 'password.confirm*', 'logout',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user instanceof User && $user->requiresTwoFactor() && ! $user->hasConfirmedTwoFactor() && ! $request->routeIs(...self::ALLOWED)) {
            if ($request->expectsJson()) {
                abort(403, __('fondasi.two_factor.required'));
            }

            return redirect()->route('fondasi.account')->with('toast', ['type' => 'warning', 'message' => __('fondasi.two_factor.required')]);
        }

        return $next($request);
    }
}
