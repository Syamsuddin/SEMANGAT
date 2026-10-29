<?php

namespace App\Modules\Fondasi\Livewire;

use App\Modules\Fondasi\Enums\Role;
use App\Modules\Fondasi\Models\User;
use App\Modules\Integrasi\Models\Employee;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

/** "Tim saya" — bawahan langsung aktif (FON-03). */
#[Layout('layouts.app')]
class TeamList extends Component
{
    public function mount(): void
    {
        /** @var User $user */
        $user = auth()->user();
        abort_unless($user->hasRole(Role::Atasan->value), 403);
    }

    /** @return Collection<int, Employee> */
    #[Computed]
    public function members(): Collection
    {
        /** @var User $user */
        $user = auth()->user();

        return Employee::query()
            ->visibleTo($user)
            ->active()
            ->where('supervisor_id', $user->employee_id)
            ->with('position:id,name')
            ->orderBy('name')
            ->get();
    }

    public function render(): View
    {
        return view('fondasi::livewire.team-list')->title(__('fondasi.team.title'));
    }
}
