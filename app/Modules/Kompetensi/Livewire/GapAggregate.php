<?php

namespace App\Modules\Kompetensi\Livewire;

use App\Modules\Fondasi\Enums\Permission;
use App\Modules\Fondasi\Models\User;
use App\Modules\Kompetensi\Services\GapAggregator;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

/** KOM-08: agregat kesenjangan per OPD/kabupaten (O/A/B) — tanpa baris individu. */
#[Layout('layouts.app')]
class GapAggregate extends Component
{
    public function mount(): void
    {
        $this->authorize(Permission::CompetencyViewAggregate->value);
    }

    /** @return array<string, mixed> */
    #[Computed]
    public function data(): array
    {
        /** @var User $user */
        $user = auth()->user();

        return app(GapAggregator::class)->forUser($user);
    }

    public function render(): View
    {
        return view('kompetensi::livewire.gap-aggregate')->title(__('kompetensi.aggregate.title'));
    }
}
