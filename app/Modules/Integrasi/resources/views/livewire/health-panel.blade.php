<div class="space-y-4" wire:poll.15s>
    <div class="flex flex-wrap items-center justify-between gap-3">
        <h1 class="text-xl font-semibold text-navy">{{ __('integrasi.panel.title') }}</h1>
        @can(\App\Modules\Fondasi\Enums\Permission::IntegrationManage->value)
            <x-button :href="route('integrasi.import')">
                <x-heroicon-o-arrow-up-tray class="size-5" aria-hidden="true" /> {{ __('integrasi.import.title') }}
            </x-button>
        @endcan
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
        <x-stat-tile :label="__('integrasi.panel.last_success')"
                     :value="$this->lastSuccess?->finished_at?->timezone(config('app.timezone'))->translatedFormat('j M Y, H.i') ?? '—'" />
        <x-stat-tile :label="__('integrasi.panel.pending_failures')" :value="number_format($this->pendingFailures, 0, ',', '.')" />
    </div>

    <div wire:loading.delay wire:target="gotoPage,nextPage,previousPage"><x-skeleton :lines="4" /></div>

    <div wire:loading.remove wire:target="gotoPage,nextPage,previousPage">
        @if ($this->runs->isEmpty())
            <x-empty-state icon="arrow-path-rounded-square" :title="__('integrasi.panel.empty')">
                @can(\App\Modules\Fondasi\Enums\Permission::IntegrationManage->value)
                    <x-button variant="secondary" :href="route('integrasi.import')">{{ __('integrasi.import.title') }}</x-button>
                @endcan
            </x-empty-state>
        @else
            <ul class="space-y-3">
                @foreach ($this->runs as $run)
                    <li wire:key="run-{{ $run->id }}" class="rounded-lg border border-line bg-surface p-4 shadow-card" data-run-status="{{ $run->status->value }}">
                        <div class="flex flex-wrap items-start justify-between gap-2">
                            <div>
                                <p class="font-medium">{{ __('integrasi.adapters.'.$run->adapter) }}</p>
                                <p class="text-xs text-muted">{{ $run->started_at->timezone(config('app.timezone'))->translatedFormat('j M Y, H.i') }} WITA</p>
                            </div>
                            <x-badge :variant="$run->status->badge()">{{ $run->status->label() }}</x-badge>
                        </div>
                        <p class="mt-2 text-sm">{{ __('integrasi.panel.counts', ['ok' => $run->rows_ok, 'failed' => $run->rows_failed]) }}</p>
                        @if ($run->error)
                            <p class="mt-1 text-sm text-danger">{{ $run->error }}</p>
                        @endif
                        <div class="mt-3 flex flex-wrap gap-2">
                            @if ($run->rows_failed > 0)
                                <x-button variant="ghost" size="sm" wire:click="toggle({{ $run->id }})" :aria-expanded="$openRunId === $run->id ? 'true' : 'false'">
                                    {{ $openRunId === $run->id ? __('integrasi.panel.hide_rows') : __('integrasi.panel.show_rows') }}
                                </x-button>
                            @endif
                            @if (in_array($run->status, [\App\Modules\Integrasi\Enums\SyncStatus::Failed, \App\Modules\Integrasi\Enums\SyncStatus::Partial], true))
                                @can('retry', $run)
                                    <x-confirm-dialog :title="__('integrasi.panel.retry_title')" :message="__('integrasi.panel.retry_message')" :confirm="__('integrasi.panel.retry')" action="retry({{ $run->id }})">
                                        <x-slot:trigger><x-button variant="secondary" size="sm">{{ __('integrasi.panel.retry') }}</x-button></x-slot:trigger>
                                    </x-confirm-dialog>
                                @endcan
                            @endif
                        </div>
                        @if ($openRunId === $run->id)
                            <ul class="mt-3 divide-y divide-line border-t border-line text-sm">
                                @foreach ($this->openFailures as $failure)
                                    <li class="py-2" wire:key="f-{{ $failure->id }}">
                                        <span class="font-medium">{{ __('integrasi.panel.row', ['n' => $failure->row_number ?? '—']) }}</span>
                                        @if ($failure->external_id) <span class="text-muted">· {{ $failure->external_id }}</span> @endif
                                        <p class="text-danger">{{ $failure->error }}</p>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </li>
                @endforeach
            </ul>
            <div>{{ $this->runs->links() }}</div>
        @endif
    </div>
</div>
