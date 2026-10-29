<?php

namespace App\Modules\Fondasi\Http\Responses;

use App\Modules\Fondasi\Models\User;
use App\Modules\Fondasi\Services\HomeRouteResolver;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Laravel\Fortify\Contracts\TwoFactorLoginResponse;
use Symfony\Component\HttpFoundation\Response;

class LoginResponse implements LoginResponseContract, TwoFactorLoginResponse
{
    public function __construct(private readonly HomeRouteResolver $resolver) {}

    public function toResponse($request): Response
    {
        /** @var User $user */
        $user = $request->user();

        return $request->wantsJson()
            ? response()->json(['two_factor' => false])
            : redirect()->intended(route($this->resolver->routeName($user)));
    }
}
