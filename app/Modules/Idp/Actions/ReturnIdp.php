<?php

namespace App\Modules\Idp\Actions;

use App\Modules\Fondasi\Enums\AuditAction;
use App\Modules\Fondasi\Models\User;
use App\Modules\Idp\Enums\IdpStatus;
use App\Modules\Idp\Models\Idp;
use App\Modules\Idp\Notifications\IdpReturned;
use App\Support\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

/** docs/06 § 4 langkah 4: atasan mengembalikan IDP dengan catatan wajib → `draft`; pegawai menerima notifikasi. */
class ReturnIdp
{
    public const MIN_NOTE_LENGTH = 5;

    public function __construct(private readonly AuditLogger $audit) {}

    public function handle(User $user, Idp $idp, ?string $note): Idp
    {
        Gate::forUser($user)->authorize('approve', $idp);

        $note = trim((string) $note);
        if (mb_strlen($note) < self::MIN_NOTE_LENGTH) {
            throw ValidationException::withMessages(['note' => __('idp.errors.note_required', ['min' => self::MIN_NOTE_LENGTH])]);
        }

        DB::transaction(function () use ($user, $idp, $note) {
            $idp->forceFill(['status' => IdpStatus::Draft, 'supervisor_note' => $note, 'submitted_at' => null])->save();
            $this->audit->log(AuditAction::Update, $idp, changes: ['status' => 'returned'], userId: $user->id);
        });

        $idp->load('employee.user');
        $idp->employee->user?->notify(new IdpReturned($idp, $note));

        return $idp;
    }
}
