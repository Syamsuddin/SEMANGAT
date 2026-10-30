<?php

namespace App\Modules\Kompetensi\Livewire;

use App\Modules\Fondasi\Enums\Role;
use App\Modules\Fondasi\Models\User;
use App\Modules\Integrasi\Models\Employee;
use App\Modules\Kompetensi\Models\Assessment;
use App\Modules\Kompetensi\Models\Gap;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

/** Akses F2: atasan melihat RINGKASAN kesenjangan tim (bawahan langsung), tanpa berkas laporan asesmen. */
#[Layout('layouts.app')]
class TeamGapSummary extends Component
{
    public function mount(): void
    {
        abort_unless(auth()->user()?->hasRole(Role::Atasan->value), 403);
    }

    /** @return Collection<int, Employee> */
    #[Computed]
    public function members(): Collection
    {
        /** @var User $user */
        $user = auth()->user();

        return Employee::query()->visibleTo($user)->active()->where('supervisor_id', $user->employee_id)
            ->with('position:id,name')->orderBy('name')->get();
    }

    /** @return \Illuminate\Database\Eloquent\Collection<int|string, \Illuminate\Database\Eloquent\Collection<int, Gap>> employee_id => gap terurut terbesar */
    #[Computed]
    public function gaps(): \Illuminate\Database\Eloquent\Collection
    {
        /** @var User $user */
        $user = auth()->user();

        return Gap::query()->visibleTo($user)->whereIn('employee_id', $this->members()->pluck('id'))
            ->with('competency:id,code,name')->orderByDesc('gap')->get()->groupBy('employee_id');
    }

    /** @return Collection<int, int> employee_id => jumlah asesmen menunggu */
    #[Computed]
    public function pendingCounts(): Collection
    {
        return Assessment::query()->pendingValidation()->whereIn('employee_id', $this->members()->pluck('id'))
            ->selectRaw('employee_id, COUNT(*) as aggregate')->groupBy('employee_id')->pluck('aggregate', 'employee_id')
            ->map(fn ($n) => (int) $n);
    }

    public function render(): View
    {
        return view('kompetensi::livewire.team-gap-summary')->title(__('kompetensi.team.title'));
    }
}
