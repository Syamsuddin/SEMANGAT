<?php

namespace App\Modules\Integrasi\Policies;

use App\Modules\Fondasi\Models\User;
use App\Modules\Integrasi\Models\Employee;

/** Lapis 1 (aksi). Data ⇅ tidak punya aksi tulis untuk peran mana pun — sumber menang. */
class EmployeePolicy
{
    public function view(User $user, Employee $employee): bool
    {
        return Employee::query()->visibleTo($user)->whereKey($employee->id)->exists();
    }

    public function update(User $user, Employee $employee): bool
    {
        return false;
    }

    public function delete(User $user, Employee $employee): bool
    {
        return false;
    }
}
