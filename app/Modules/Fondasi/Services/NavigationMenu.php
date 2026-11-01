<?php

namespace App\Modules\Fondasi\Services;

use App\Modules\Fondasi\Enums\Permission;
use App\Modules\Fondasi\Enums\Role;
use App\Modules\Fondasi\Models\User;

/** Butir navigasi per peran. Ponsel: 4 butir pertama (bottom nav); ≥1024: sidebar penuh. */
class NavigationMenu
{
    /** @return list<array{label: string, route: string, icon: string, active: string}> */
    public function for(User $user): array
    {
        $items = [['label' => __('fondasi.nav.home'), 'route' => 'fondasi.home', 'icon' => 'home', 'active' => 'fondasi.home']];

        if ($user->employee_id !== null) {
            $items[] = ['label' => __('kompetensi.nav.profile'), 'route' => 'kompetensi.profile.show', 'icon' => 'academic-cap', 'active' => 'kompetensi.profile.*'];
            $items[] = ['label' => __('idp.nav.mine'), 'route' => 'idp.plans.mine', 'icon' => 'clipboard-document-list', 'active' => 'idp.plans.*'];
        }

        if ($user->hasRole(Role::Atasan->value)) {
            $items[] = ['label' => __('fondasi.nav.team'), 'route' => 'fondasi.team.index', 'icon' => 'user-group', 'active' => 'fondasi.team.*'];
            $items[] = ['label' => __('kompetensi.nav.team'), 'route' => 'kompetensi.team.index', 'icon' => 'chart-bar-square', 'active' => 'kompetensi.team.*'];
            $items[] = ['label' => __('idp.nav.team'), 'route' => 'idp.team.index', 'icon' => 'document-check', 'active' => 'idp.team.*'];
        }

        if ($user->can(Permission::IdpViewUnlinked->value) && ! $user->hasRole(Role::Atasan->value)) {
            $items[] = ['label' => __('idp.nav.unlinked'), 'route' => 'idp.unlinked.index', 'icon' => 'link-slash', 'active' => 'idp.unlinked.*'];
        }

        if ($user->can(Permission::CompetencyViewAggregate->value)) {
            $items[] = ['label' => __('kompetensi.nav.aggregate'), 'route' => 'kompetensi.aggregate.index', 'icon' => 'chart-bar', 'active' => 'kompetensi.aggregate.*'];
        }

        if ($user->can(Permission::EmployeeViewAny->value) && ! $user->hasRole(Role::Atasan->value)) {
            $items[] = ['label' => __('fondasi.nav.employees'), 'route' => 'fondasi.employees.index', 'icon' => 'users', 'active' => 'fondasi.employees.index'];
        }

        if ($user->employee_id !== null) {
            $items[] = ['label' => __('fondasi.nav.profile'), 'route' => 'fondasi.profile', 'icon' => 'identification', 'active' => 'fondasi.profile'];
        }

        if ($user->can(Permission::CompetencyManage->value)) {
            $items[] = ['label' => __('kompetensi.nav.import'), 'route' => 'kompetensi.import', 'icon' => 'arrow-up-tray', 'active' => 'kompetensi.import'];
        }

        if ($user->can(Permission::IntegrationView->value)) {
            $items[] = ['label' => __('fondasi.nav.integration'), 'route' => 'integrasi.panel', 'icon' => 'arrow-path-rounded-square', 'active' => 'integrasi.*'];
        }

        $items[] = ['label' => __('fondasi.nav.account'), 'route' => 'fondasi.account', 'icon' => 'user-circle', 'active' => 'fondasi.account'];

        if ($user->can(Permission::EmployeeViewAny->value) && $user->hasRole(Role::Atasan->value)) {
            $items[] = ['label' => __('fondasi.nav.employees'), 'route' => 'fondasi.employees.index', 'icon' => 'users', 'active' => 'fondasi.employees.index'];
        }

        return $items;
    }

    /**
     * Bottom nav ponsel: maksimal 4 butir; "Akun" selalu ada.
     *
     * @return list<array{label: string, route: string, icon: string, active: string}>
     */
    public function mobile(User $user): array
    {
        $items = $this->for($user);
        $account = array_values(array_filter($items, fn ($i) => $i['route'] === 'fondasi.account'));
        $others = array_values(array_filter($items, fn ($i) => $i['route'] !== 'fondasi.account'));

        return [...array_slice($others, 0, 3), ...$account];
    }
}
