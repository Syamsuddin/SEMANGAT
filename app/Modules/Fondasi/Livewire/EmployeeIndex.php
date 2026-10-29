<?php

namespace App\Modules\Fondasi\Livewire;

use App\Modules\Fondasi\Enums\Permission;
use App\Modules\Fondasi\Models\User;
use App\Modules\Integrasi\Enums\EmployeeStatus;
use App\Modules\Integrasi\Models\Employee;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/** Data pegawai (daftar+filter) — isi selalu lewat visibleTo (FON-04). */
#[Layout('layouts.app')]
class EmployeeIndex extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url]
    public string $status = 'active';

    public function mount(): void
    {
        $this->authorize(Permission::EmployeeViewAny->value);
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['search', 'status'], true)) {
            $this->resetPage();
        }
    }

    /** @return LengthAwarePaginator<int, Employee> */
    #[Computed]
    public function employees(): LengthAwarePaginator
    {
        /** @var User $user */
        $user = auth()->user();
        $search = trim($this->search);

        return Employee::query()
            ->visibleTo($user)
            ->with(['opd:id,name', 'position:id,name'])
            ->when(EmployeeStatus::tryFrom($this->status), fn ($q, $s) => $q->where('status', $s->value))
            ->when($search !== '', fn ($q) => $q->where(fn ($w) => $w->where('name', 'like', "%{$search}%")->orWhere('nip', 'like', "{$search}%")))
            ->orderBy('name')
            ->paginate(20);
    }

    public function render(): View
    {
        return view('fondasi::livewire.employee-index')->title(__('fondasi.employees.title'));
    }
}
