<?php

namespace App\Modules\Fondasi\Services;

use App\Modules\Fondasi\Enums\Role;
use App\Modules\Fondasi\Models\User;

/** FON-01: beranda sesuai peran utama (urutan Role::primaryOrder). */
class HomeRouteResolver
{
    public function primaryRole(User $user): ?Role
    {
        foreach (Role::primaryOrder() as $role) {
            if ($user->hasRole($role->value)) {
                return $role;
            }
        }

        return null;
    }

    public function routeName(User $user): string
    {
        return match ($this->primaryRole($user)) {
            Role::Pengelola => 'integrasi.panel',
            default => 'fondasi.home',
        };
    }
}
