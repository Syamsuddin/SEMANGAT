<?php

namespace App\Support\Scope;

use App\Modules\Fondasi\Enums\Role;
use App\Modules\Fondasi\Models\RoleAssignment;
use App\Modules\Fondasi\Models\User;
use App\Modules\Integrasi\Models\Employee;
use Illuminate\Database\Eloquent\Builder;

/**
 * Cakupan data per peran (docs/05 matriks; docs/21 lapis 2).
 * Data pegawai individu: S sendiri · T bawahan langsung · O OPD penugasan · K/B seluruh.
 * clo & dewan_pengarah hanya agregat (A/R) → tidak melihat baris individu selain dirinya.
 */
final class DataScope
{
    private function __construct(private readonly User $user) {}

    public static function for(User $user): self
    {
        return new self($user);
    }

    public function seesAllEmployees(): bool
    {
        return $this->user->hasAnyRole([Role::Pengelola->value, Role::Auditor->value]);
    }

    /** @return list<int> */
    public function opdIds(): array
    {
        if (! $this->user->hasRole(Role::KoordinatorOpd->value)) {
            return [];
        }

        return RoleAssignment::query()
            ->current()
            ->where('user_id', $this->user->id)
            ->where('role', Role::KoordinatorOpd->value)
            ->where('scope_type', RoleAssignment::SCOPE_OPD)
            ->pluck('scope_id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
    }

    /**
     * @param  Builder<Employee>  $query
     * @return Builder<Employee>
     */
    public function applyToEmployees(Builder $query): Builder
    {
        if (! $this->user->is_active) {
            return $query->whereRaw('1 = 0');
        }

        if ($this->seesAllEmployees()) {
            return $query;
        }

        $employeeId = $this->user->employee_id;
        $opdIds = $this->opdIds();

        return $query->where(function (Builder $q) use ($employeeId, $opdIds) {
            $q->whereRaw('1 = 0');

            if ($employeeId !== null) {
                $q->orWhere('hr_employees.id', $employeeId)
                    ->orWhere('hr_employees.supervisor_id', $employeeId);
            }

            if ($opdIds !== []) {
                $q->orWhereIn('hr_employees.opd_id', $opdIds);
            }
        });
    }
}
