<?php

namespace App\Modules\Fondasi\Livewire;

use App\Modules\Fondasi\Models\User;
use App\Modules\Integrasi\Models\Employee;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

/** Profil pegawai. Di luar cakupan → 404, bukan 403, agar keberadaan data tidak bocor (FON-04). */
#[Layout('layouts.app')]
class EmployeeShow extends Component
{
    #[Locked]
    public int $employeeId;

    public function mount(int $employee): void
    {
        $this->employeeId = $employee;
        $this->employee();
    }

    private function employee(): Employee
    {
        /** @var User $user */
        $user = auth()->user();

        return Employee::query()
            ->visibleTo($user)
            ->with(['opd', 'orgUnit', 'position', 'supervisor:id,name,nip'])
            ->findOrFail($this->employeeId);
    }

    public function render(): View
    {
        $employee = $this->employee();

        return view('fondasi::livewire.employee-show', [
            'employee' => $employee,
            'isSelf' => auth()->user()?->employee_id === $employee->id,
        ])->title($employee->name);
    }
}
