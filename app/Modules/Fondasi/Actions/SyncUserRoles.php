<?php

namespace App\Modules\Fondasi\Actions;

use App\Modules\Fondasi\Enums\Role;
use App\Modules\Fondasi\Models\RoleAssignment;
use App\Modules\Fondasi\Models\User;
use App\Modules\Integrasi\Models\Employee;

/**
 * FON-03: satu-satunya tempat peran spatie ditetapkan.
 * asn = pegawai aktif; atasan = punya bawahan langsung aktif; sisanya dari sys_role_assignments yang berlaku.
 */
class SyncUserRoles
{
    public function __invoke(User $user): void
    {
        $user->syncRoles($this->desiredRoles($user));
    }

    public function forAll(): void
    {
        User::query()->with('employee')->chunkById(500, function ($users) {
            foreach ($users as $user) {
                $this($user);
            }
        });
    }

    /** @return list<string> */
    public function desiredRoles(User $user): array
    {
        $roles = [];
        $employee = $user->employee;

        if ($user->is_active && $employee !== null && $employee->isActive()) {
            $roles[] = Role::Asn->value;

            if (Employee::query()->active()->where('supervisor_id', $employee->id)->exists()) {
                $roles[] = Role::Atasan->value;
            }
        }

        if ($user->is_active) {
            $assigned = RoleAssignment::query()->current()->where('user_id', $user->id)->pluck('role');
            foreach ($assigned as $role) {
                if (! $role->isDerived()) {
                    $roles[] = $role->value;
                }
            }
        }

        return array_values(array_unique($roles));
    }
}
