<?php

namespace App\Modules\JpRiwayat\Livewire;

use App\Modules\Fondasi\Enums\Permission;
use App\Modules\Fondasi\Models\User;
use App\Modules\JpRiwayat\Services\JpRecap;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

/** JPR-06: rekap pemenuhan 20 JP — koord. OPD: OPD-nya (O); pengelola/auditor: kabupaten (K/B); clo/dewan: agregat (A/R). */
#[Layout('layouts.app')]
class JpRecapReport extends Component
{
    public int $year;

    public function mount(): void
    {
        $this->authorize(Permission::JpViewRecap->value);
        $this->year = (int) now()->year;
    }

    /** @return array<string, mixed> */
    #[Computed]
    public function data(): array
    {
        /** @var User $user */
        $user = auth()->user();

        return app(JpRecap::class)->forUser($user, $this->year);
    }

    /** @return array<int, int> */
    public function yearOptions(): array
    {
        $years = range((int) now()->year, (int) now()->year - 4);

        return array_combine($years, $years);
    }

    public function updatedYear(): void
    {
        unset($this->data);
    }

    public function render(): View
    {
        return view('jp::livewire.jp-recap-report')->title(__('jp.recap.title'));
    }
}
