<?php

namespace App\Modules\Kompetensi\Livewire;

use App\Modules\Fondasi\Models\User;
use App\Modules\Kompetensi\Actions\RecordSelfAssessment;
use App\Modules\Kompetensi\Models\Assessment;
use App\Modules\Kompetensi\Models\JobStandard;
use App\Modules\Kompetensi\Models\JobStandardItem;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

/** KOM-06: ASN mengisi asesmen mandiri untuk kompetensi SKJ jabatannya. */
#[Layout('layouts.app')]
class SelfAssessmentForm extends Component
{
    /** @var array<int|string, int|string|null> competency_id => level */
    public array $levels = [];

    public function mount(): void
    {
        abort_if(auth()->user()?->employee_id === null, 404);
        foreach ($this->pending() as $assessment) {
            $this->levels[$assessment->competency_id] = (string) $assessment->level;
        }
    }

    /** @return Collection<int, JobStandardItem> */
    #[Computed]
    public function items(): Collection
    {
        /** @var User $user */
        $user = auth()->user();

        return JobStandard::forPosition($user->employee?->position)?->items->sortBy('competency.name')->values() ?? collect();
    }

    /** @return Collection<int, Assessment> */
    #[Computed]
    public function pending(): Collection
    {
        return Assessment::query()->select(Assessment::NON_SENSITIVE_COLUMNS)->pendingValidation()
            ->where('employee_id', auth()->user()?->employee_id)->get();
    }

    public function submit(RecordSelfAssessment $record): void
    {
        /** @var User $user */
        $user = auth()->user();
        $record($user, $this->levels);

        session()->flash('toast', ['type' => 'success', 'message' => __('kompetensi.self.submitted')]);
        $this->redirectRoute('kompetensi.profile.show', navigate: false);
    }

    public function render(): View
    {
        return view('kompetensi::livewire.self-assessment-form')->title(__('kompetensi.self.title'));
    }
}
