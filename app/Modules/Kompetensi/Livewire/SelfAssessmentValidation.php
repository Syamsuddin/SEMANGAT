<?php

namespace App\Modules\Kompetensi\Livewire;

use App\Modules\Fondasi\Enums\Role;
use App\Modules\Fondasi\Models\User;
use App\Modules\Kompetensi\Actions\ValidateSelfAssessment;
use App\Modules\Kompetensi\Models\Assessment;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

/** KOM-06: atasan menyetujui/mengembalikan asesmen mandiri bawahan langsung. */
#[Layout('layouts.app')]
class SelfAssessmentValidation extends Component
{
    /** @var array<int|string, string> assessment_id => alasan pengembalian */
    public array $notes = [];

    public function mount(): void
    {
        abort_unless(auth()->user()?->hasRole(Role::Atasan->value), 403);
    }

    /** @return Collection<int, Assessment> */
    #[Computed]
    public function pending(): Collection
    {
        /** @var User $user */
        $user = auth()->user();

        return Assessment::query()->visibleTo($user)->select(Assessment::NON_SENSITIVE_COLUMNS)->pendingValidation()
            ->whereHas('employee', fn ($q) => $q->where('supervisor_id', $user->employee_id))
            ->with(['employee:id,name,nip,position_id', 'employee.position:id,name', 'competency:id,code,name'])
            ->orderBy('employee_id')->orderBy('competency_id')->get();
    }

    public function approve(int $assessmentId, ValidateSelfAssessment $validate): void
    {
        $validate->approve($this->user(), $this->find($assessmentId));
        unset($this->pending);
        $this->dispatch('toast', type: 'success', message: __('kompetensi.validation.approved'));
    }

    public function reject(int $assessmentId, ValidateSelfAssessment $validate): void
    {
        $this->resetErrorBag();
        $validate->reject($this->user(), $this->find($assessmentId), $this->notes[$assessmentId] ?? null);
        unset($this->pending, $this->notes[$assessmentId]);
        $this->dispatch('toast', type: 'success', message: __('kompetensi.validation.rejected'));
    }

    private function find(int $assessmentId): Assessment
    {
        return Assessment::query()->visibleTo($this->user())->with('employee')->findOrFail($assessmentId);
    }

    private function user(): User
    {
        /** @var User $user */
        $user = auth()->user();

        return $user;
    }

    public function render(): View
    {
        return view('kompetensi::livewire.self-assessment-validation')->title(__('kompetensi.validation.title'));
    }
}
