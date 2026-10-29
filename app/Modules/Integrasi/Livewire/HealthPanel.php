<?php

namespace App\Modules\Integrasi\Livewire;

use App\Modules\Fondasi\Models\User;
use App\Modules\Integrasi\Actions\RetrySyncRun;
use App\Modules\Integrasi\Enums\SyncStatus;
use App\Modules\Integrasi\Models\SyncFailure;
use App\Modules\Integrasi\Models\SyncRun;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

/** INT-08: waktu sinkron terakhir, jumlah gagal, antrean ulang. */
#[Layout('layouts.app')]
class HealthPanel extends Component
{
    use WithPagination;

    public ?int $openRunId = null;

    public function mount(): void
    {
        $this->authorize('viewAny', SyncRun::class);
    }

    /** @return LengthAwarePaginator<int, SyncRun> */
    #[Computed]
    public function runs(): LengthAwarePaginator
    {
        return SyncRun::query()->latest('started_at')->latest('id')->paginate(15);
    }

    #[Computed]
    public function lastSuccess(): ?SyncRun
    {
        return SyncRun::query()->where('status', SyncStatus::Success->value)->latest('finished_at')->first();
    }

    #[Computed]
    public function pendingFailures(): int
    {
        return SyncFailure::query()->whereNull('resolved_at')->whereNull('retried_at')->count();
    }

    /** @return Collection<int, SyncFailure> */
    #[Computed]
    public function openFailures(): Collection
    {
        return $this->openRunId === null ? collect()
            : SyncFailure::query()->where('sync_run_id', $this->openRunId)->orderBy('row_number')->limit(200)->get();
    }

    public function toggle(int $runId): void
    {
        $this->authorize('viewAny', SyncRun::class);
        $this->openRunId = $this->openRunId === $runId ? null : $runId;
    }

    public function retry(int $runId, RetrySyncRun $retry): void
    {
        $run = SyncRun::query()->findOrFail($runId);
        $this->authorize('retry', $run);
        /** @var User $user */
        $user = auth()->user();
        $retry($run, $user);
        unset($this->runs);
        $this->dispatch('toast', type: 'success', message: __('integrasi.panel.retry_queued'));
    }

    public function render(): View
    {
        return view('integrasi::livewire.health-panel')->title(__('integrasi.panel.title'));
    }
}
