<?php

namespace App\Modules\Fondasi\Actions;

use App\Modules\Fondasi\Enums\AuditAction;
use App\Modules\Fondasi\Enums\Role;
use App\Modules\Fondasi\Models\RoleAssignment;
use App\Modules\Fondasi\Models\User;
use App\Modules\Integrasi\Enums\OrgUnitLevel;
use App\Modules\Integrasi\Models\OrgUnit;
use App\Support\AuditLogger;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Penugasan peran manual tercatat (docs/05). Peran turunan ditolak. */
class AssignRole
{
    public function __construct(private readonly AuditLogger $audit, private readonly SyncUserRoles $syncRoles) {}

    public function __invoke(User $target, Role $role, string $reason, ?User $by = null, ?int $opdId = null, ?CarbonInterface $endsAt = null): RoleAssignment
    {
        if ($role->isDerived()) {
            throw ValidationException::withMessages(['role' => __('fondasi.roles.derived_not_assignable')]);
        }

        if (trim($reason) === '') {
            throw ValidationException::withMessages(['reason' => __('fondasi.roles.reason_required')]);
        }

        if ($role->requiresOpdScope()) {
            $isOpd = $opdId !== null && OrgUnit::query()->whereKey($opdId)->where('level', OrgUnitLevel::Opd->value)->exists();
            if (! $isOpd) {
                throw ValidationException::withMessages(['opd_id' => __('fondasi.roles.opd_required')]);
            }
        }

        return DB::transaction(function () use ($target, $role, $reason, $by, $opdId, $endsAt) {
            $assignment = RoleAssignment::query()->create([
                'user_id' => $target->id,
                'role' => $role,
                'scope_type' => $role->requiresOpdScope() ? RoleAssignment::SCOPE_OPD : null,
                'scope_id' => $role->requiresOpdScope() ? $opdId : null,
                'reason' => $reason,
                'assigned_by' => $by?->id,
                'starts_at' => now(),
                'ends_at' => $endsAt,
            ]);

            $this->audit->log(AuditAction::RoleAssign, $assignment, $reason, [
                'user_id' => $target->id, 'role' => $role->value, 'scope_id' => $assignment->scope_id,
            ], $by?->id);

            ($this->syncRoles)($target);

            return $assignment;
        });
    }
}
