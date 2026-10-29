<?php

namespace App\Modules\Fondasi\Database\Seeders;

use App\Modules\Fondasi\Enums\Permission;
use App\Modules\Fondasi\Enums\Role;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission as PermissionModel;
use Spatie\Permission\Models\Role as RoleModel;
use Spatie\Permission\PermissionRegistrar;

/** Sembilan peran + izin F1 (docs/05). Idempoten; aman di produksi. */
class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (Role::cases() as $role) {
            RoleModel::findOrCreate($role->value, 'web');
        }

        foreach (Permission::cases() as $permission) {
            PermissionModel::findOrCreate($permission->value, 'web');
        }

        foreach (Role::cases() as $role) {
            $granted = array_values(array_map(
                fn (Permission $p) => $p->value,
                array_filter(Permission::cases(), fn (Permission $p) => in_array($role, $p->roles(), true)),
            ));
            RoleModel::findByName($role->value, 'web')->syncPermissions($granted);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
