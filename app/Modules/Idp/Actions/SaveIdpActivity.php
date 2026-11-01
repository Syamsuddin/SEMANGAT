<?php

namespace App\Modules\Idp\Actions;

use App\Modules\Fondasi\Models\User;
use App\Modules\Idp\Enums\ActivityPortion;
use App\Modules\Idp\Models\IdpActivity;
use App\Modules\Idp\Models\IdpItem;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/** IDP-02: aktivitas 10:20:70 per butir, dengan target JP dan jadwal. */
class SaveIdpActivity
{
    /** @param  array<string, mixed>  $input */
    public function handle(User $user, IdpItem $item, array $input): IdpActivity
    {
        Gate::forUser($user)->authorize('update', $item->idp);

        $data = Validator::make($input, [
            'portion' => ['required', 'integer', Rule::enum(ActivityPortion::class)],
            'title' => ['required', 'string', 'max:200'],
            'planned_jp' => ['required', 'integer', 'min:0', 'max:500'],
            'planned_start' => ['nullable', 'date'],
            'planned_end' => ['nullable', 'date', 'after_or_equal:planned_start'],
        ], [], __('idp.attributes'))->validate();

        return $item->activities()->create($data);
    }

    public function remove(User $user, IdpActivity $activity): void
    {
        Gate::forUser($user)->authorize('update', $activity->item->idp);

        $activity->delete();
    }
}
