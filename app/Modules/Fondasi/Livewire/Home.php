<?php

namespace App\Modules\Fondasi\Livewire;

use App\Modules\Fondasi\Models\User;
use App\Modules\Fondasi\Services\HomeRouteResolver;
use App\Modules\Integrasi\Models\Employee;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Home extends Component
{
    public function render(HomeRouteResolver $resolver): View
    {
        /** @var User $user */
        $user = auth()->user();
        $employee = $user->employee_id === null ? null
            : Employee::query()->visibleTo($user)->with(['opd', 'position'])->find($user->employee_id);

        return view('fondasi::livewire.home', [
            'user' => $user,
            'employee' => $employee,
            'primaryRole' => $resolver->primaryRole($user),
            'teamCount' => $user->employee_id === null ? 0
                : Employee::query()->visibleTo($user)->active()->where('supervisor_id', $user->employee_id)->count(),
        ])->title(__('fondasi.nav.home'));
    }
}
