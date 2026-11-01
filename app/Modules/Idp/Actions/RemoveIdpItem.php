<?php

namespace App\Modules\Idp\Actions;

use App\Modules\Fondasi\Models\User;
use App\Modules\Idp\Models\Idp;
use App\Modules\Idp\Models\IdpItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

/** Hapus butir (beserta aktivitasnya) dari IDP `draft`; posisi dirapatkan kembali 1..n. */
class RemoveIdpItem
{
    public function handle(User $user, Idp $idp, IdpItem $item): void
    {
        Gate::forUser($user)->authorize('update', $idp);
        abort_if($item->idp_id !== $idp->id, 404);

        DB::transaction(function () use ($idp, $item) {
            $item->delete();
            $idp->items()->get()->each(fn (IdpItem $it, int $i) => $it->update(['position' => $i + 1]));
        });
    }
}
