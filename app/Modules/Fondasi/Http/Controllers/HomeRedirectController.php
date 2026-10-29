<?php

namespace App\Modules\Fondasi\Http\Controllers;

use App\Modules\Fondasi\Models\User;
use App\Modules\Fondasi\Services\HomeRouteResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class HomeRedirectController
{
    public function __invoke(Request $request, HomeRouteResolver $resolver): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        return redirect()->route($resolver->routeName($user));
    }
}
