<?php

namespace App\Modules\Integrasi\Policies;

use App\Modules\Fondasi\Enums\Permission;
use App\Modules\Fondasi\Models\User;
use App\Modules\Integrasi\Models\SyncRun;

class SyncRunPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Permission::IntegrationView->value);
    }

    public function retry(User $user, SyncRun $run): bool
    {
        return $user->can(Permission::IntegrationManage->value);
    }
}
