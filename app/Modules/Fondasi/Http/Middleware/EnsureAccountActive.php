<?php

namespace App\Modules\Fondasi\Http\Middleware;

use App\Modules\Fondasi\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/** Akun dinonaktifkan sinkron (pegawai inactive) → sesi berjalan diakhiri. */
class EnsureAccountActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user instanceof User && ! $user->is_active) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors(['username' => __('fondasi.auth.inactive')]);
        }

        return $next($request);
    }
}
