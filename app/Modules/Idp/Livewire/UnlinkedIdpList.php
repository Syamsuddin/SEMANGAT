<?php

namespace App\Modules\Idp\Livewire;

use App\Modules\Fondasi\Enums\Permission;
use App\Modules\Fondasi\Models\User;
use App\Modules\Idp\Models\Idp;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

/** IDP-10: daftar "IDP tanpa tautan SKP" — atasan (tim), koordinator OPD (OPD), pengelola/auditor (seluruh). */
#[Layout('layouts.app')]
class UnlinkedIdpList extends Component
{
    use WithPagination;

    public function mount(): void
    {
        abort_unless($this->user()->can(Permission::IdpViewUnlinked->value), 403);
    }

    /** @return LengthAwarePaginator<int, Idp> */
    #[Computed]
    public function idps(): LengthAwarePaginator
    {
        return Idp::query()->visibleTo($this->user())->unlinked()
            ->with(['employee:id,name,nip,opd_id,position_id', 'employee.position:id,name', 'employee.opd:id,name'])
            ->orderByDesc('unlinked_items_count')->orderBy('employee_id')
            ->paginate(25);
    }

    public function render(): View
    {
        return view('idp::livewire.unlinked-idp-list')->title(__('idp.unlinked_list.title'));
    }

    private function user(): User
    {
        /** @var User $user */
        $user = auth()->user();

        return $user;
    }
}
