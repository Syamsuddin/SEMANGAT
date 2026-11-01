<?php

namespace App\Modules\Idp\Actions;

use App\Modules\Fondasi\Models\User;
use App\Modules\Idp\Models\Idp;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;

/** IDP-02/03: target JP dan aspirasi karier — hanya saat `draft`. */
class UpdateIdp
{
    /** @param  array<string, mixed>  $input */
    public function handle(User $user, Idp $idp, array $input): Idp
    {
        Gate::forUser($user)->authorize('update', $idp);

        $data = Validator::make($input, [
            'career_aspiration' => ['nullable', 'string', 'max:2000'],
            'target_jp' => ['required', 'integer', 'min:0', 'max:500'],
        ], [], __('idp.attributes'))->validate();

        $idp->fill($data)->save();

        return $idp;
    }
}
