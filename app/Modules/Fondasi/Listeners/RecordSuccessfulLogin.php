<?php

namespace App\Modules\Fondasi\Listeners;

use App\Modules\Fondasi\Enums\AuditAction;
use App\Modules\Fondasi\Models\User;
use App\Support\AuditLogger;
use Illuminate\Auth\Events\Login;

/** Sekali per sesi terbentuk (juga setelah tantangan 2FA): last_login_at + audit `login`. */
class RecordSuccessfulLogin
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function handle(Login $event): void
    {
        if (! $event->user instanceof User) {
            return;
        }

        $event->user->forceFill(['last_login_at' => now()])->save();
        $this->audit->log(AuditAction::Login, $event->user, userId: $event->user->id);
    }
}
