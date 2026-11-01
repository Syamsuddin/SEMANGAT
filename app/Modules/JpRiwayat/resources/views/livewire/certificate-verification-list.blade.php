<div class="space-y-4">
    <h1 class="text-xl font-semibold text-navy">{{ __('jp.verify.title') }}</h1>
    <p class="text-sm text-muted">{{ __('jp.verify.intro') }}</p>

    <div class="max-w-sm">
        <x-input :label="__('jp.verify.search')" name="search" type="search" wire:model.live.debounce.400ms="search" />
    </div>

    <div wire:loading.delay wire:target="search,verify,reject"><x-skeleton :lines="3" /></div>
    @error('jp') <x-error-state :message="$message" /> @enderror
    @error('reject_reason') <x-error-state :message="$message" /> @enderror

    <div wire:loading.remove wire:target="search,verify,reject">
        @if ($this->pending->isEmpty())
            <x-empty-state icon="check-circle" :title="__('jp.verify.empty')" />
        @else
            <ul class="grid gap-3 lg:grid-cols-2">
                @foreach ($this->pending as $record)
                    <li wire:key="v-{{ $record->id }}" class="space-y-3 rounded-lg border border-line bg-surface p-4 shadow-card">
                        <div>
                            <p class="font-medium text-ink">{{ $record->employee->name }} <span class="text-xs text-muted">{{ $record->employee->nip }} · {{ $record->employee->opd->name }}</span></p>
                            <p class="text-sm">{{ $record->title }}</p>
                            <p class="text-xs text-muted">
                                {{ $record->certificate?->issuer }} @if ($record->certificate?->number) · {{ __('jp.attributes.number') }} {{ $record->certificate->number }} @endif
                                · {{ $record->activity_start->translatedFormat('j M Y') }}–{{ $record->activity_end->translatedFormat('j M Y') }}
                                · {{ $record->portion->label() }} · {{ $record->mode->label() }}
                            </p>
                            <p class="text-sm">{{ __('jp.verify.claimed', ['jp' => \Illuminate\Support\Number::format((float) $record->jp, maxPrecision: 1, locale: 'id')]) }}</p>
                        </div>
                        <a href="{{ route('jp.certificates.download', $record) }}" class="inline-flex min-h-11 items-center gap-1 text-sm text-teal-700 underline-offset-2 hover:underline">
                            <x-heroicon-o-arrow-down-tray class="size-4" aria-hidden="true" />{{ __('jp.history.download') }}
                        </a>
                        <div class="flex flex-wrap items-end gap-2">
                            <div class="w-32">
                                <x-input :label="__('jp.verify.jp_correction')" name="jp" id="jp-{{ $record->id }}" type="number" step="0.5" min="0.5" max="999.9" wire:model="jp.{{ $record->id }}" :placeholder="(string) $record->jp" />
                            </div>
                            <x-button wire:click="verify({{ $record->id }})" loading="verify({{ $record->id }})">{{ __('jp.verify.verify') }}</x-button>
                        </div>
                        <details>
                            <summary class="min-h-11 cursor-pointer text-sm font-medium text-danger">{{ __('jp.verify.reject') }}</summary>
                            <div class="mt-2 space-y-2">
                                <x-textarea :label="__('jp.attributes.reject_reason')" name="reject_reason" id="rr-{{ $record->id }}" wire:model="reasons.{{ $record->id }}" rows="2" maxlength="1000" />
                                <x-confirm-dialog :title="__('jp.verify.reject_title')" :message="__('jp.verify.reject_body')" :confirm="__('jp.verify.reject')" action="reject({{ $record->id }})" variant="danger">
                                    <x-slot:trigger><x-button variant="danger" class="w-full sm:w-auto">{{ __('jp.verify.reject') }}</x-button></x-slot:trigger>
                                </x-confirm-dialog>
                            </div>
                        </details>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</div>
