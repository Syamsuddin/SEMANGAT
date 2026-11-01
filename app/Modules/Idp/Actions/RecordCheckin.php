<?php

namespace App\Modules\Idp\Actions;

use App\Modules\Fondasi\Models\User;
use App\Modules\Idp\Models\Idp;
use App\Modules\Idp\Models\IdpCheckin;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/** IDP-06: check-in triwulanan oleh pegawai (progres per butir 0–100 %); satu check-in per triwulan. */
class RecordCheckin
{
    /** @param  array<int|string, mixed>  $progress  item_id => persen */
    public function handle(User $user, Idp $idp, ?string $note, array $progress, ?Carbon $on = null): IdpCheckin
    {
        Gate::forUser($user)->authorize('checkin', $idp);

        $on ??= now();
        $itemIds = $idp->items()->pluck('id')->map(fn ($id) => (string) $id)->all();

        $data = Validator::make(['employee_note' => $note, 'progress' => $progress], [
            'employee_note' => ['required', 'string', 'min:5', 'max:2000'],
            'progress' => ['required', 'array:'.implode(',', $itemIds)],
            'progress.*' => ['required', 'integer', 'min:0', 'max:100'],
        ], [], __('idp.attributes'))->validate();

        if ($idp->checkins()->where('quarter', $on->quarter)->exists()) {
            throw ValidationException::withMessages(['employee_note' => __('idp.errors.checkin_exists', ['quarter' => $on->quarter])]);
        }

        return $idp->checkins()->create([
            'checkin_on' => $on->toDateString(),
            'quarter' => $on->quarter,
            'employee_note' => $data['employee_note'],
            'progress' => array_map('intval', $data['progress']),
        ]);
    }

    /** Catatan atasan aktif atas check-in (docs/06 § 4 langkah 6). */
    public function review(User $user, IdpCheckin $checkin, ?string $note): IdpCheckin
    {
        Gate::forUser($user)->authorize('supervise', $checkin->idp);

        $data = Validator::make(['supervisor_note' => $note], ['supervisor_note' => ['required', 'string', 'min:5', 'max:2000']], [], __('idp.attributes'))->validate();
        $checkin->forceFill($data)->save();

        return $checkin;
    }
}
