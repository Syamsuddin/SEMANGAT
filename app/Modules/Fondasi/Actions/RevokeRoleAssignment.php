<?php

namespace App\Modules\Fondasi\Actions;

use App\Modules\Fondasi\Enums\AuditAction;
use App\Modules\Fondasi\Models\RoleAssignment;
use App\Modules\Fondasi\Models\User;
use App\Support\AuditLogger;
use Illuminate\Support\Facades\DB;

class RevokeRoleAssignment
{
    public function __construct(private readonly AuditLogger $audit, private readonly SyncUserRoles $syncRoles) {}

    public function __invoke(RoleAssignment $assignment, string $reason, ?User $by = null): void
    {
        DB::transaction(function () use ($assignment, $reason, $by) {
            $assignment->forceFill(['revoked_at' => now()])->save();
            $this->audit->log(AuditAction::RoleRevoke, $assignment, $reason, ['role' => $assignment->role->value], $by?->id);
            ($this->syncRoles)($assignment->user);
        });
    }
}
