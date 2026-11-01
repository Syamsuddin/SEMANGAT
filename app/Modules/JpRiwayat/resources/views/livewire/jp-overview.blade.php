<div class="space-y-4">
    <div class="flex flex-wrap items-center justify-between gap-2">
        <h1 class="text-xl font-semibold text-navy">
            {{ $this->isOwner ? __('jp.overview.title') : __('jp.overview.title_employee', ['name' => $this->employee->name]) }}
        </h1>
        <div class="w-36">
            <x-select :label="__('jp.overview.year')" name="year" wire:model.live="year" :options="array_combine($this->years, $this->years)" />
        </div>
    </div>
    <p class="text-sm text-muted"><abbr title="{{ __('jp.overview.jp_tooltip') }}">JP</abbr> — {{ __('jp.overview.intro') }}</p>

    {{-- Saldo 20 JP (JPR-01): hanya `verified` dihitung --}}
    <div wire:loading.delay wire:target="year"><x-skeleton :lines="2" /></div>
    <x-card class="space-y-3" wire:loading.remove wire:target="year">
        <div class="flex flex-wrap items-end justify-between gap-2">
            <div>
                <p class="text-xs text-muted">{{ __('jp.overview.balance_label', ['year' => $this->balance['year']]) }}</p>
                <p class="text-2xl font-bold text-navy">{{ __('jp.overview.balance', ['verified' => \Illuminate\Support\Number::format($this->balance['verified'], maxPrecision: 1, locale: 'id'), 'target' => $this->balance['target']]) }}</p>
            </div>
            @if ($this->balance['fulfilled'])
                <x-badge variant="success"><x-heroicon-s-check-badge class="size-4" aria-hidden="true" />{{ __('jp.overview.fulfilled') }}</x-badge>
            @else
                <x-badge variant="warning"><x-heroicon-s-clock class="size-4" aria-hidden="true" />{{ __('jp.overview.not_fulfilled') }}</x-badge>
            @endif
        </div>
        <x-progress :value="$this->balance['percent']" :label="__('jp.overview.progress')" />
        @if ($this->balance['pending'] > 0)
            <p class="text-xs text-muted">{{ __('jp.overview.pending', ['jp' => \Illuminate\Support\Number::format($this->balance['pending'], maxPrecision: 1, locale: 'id')]) }}</p>
        @endif
    </x-card>

    {{-- Laporan Hasil Pembelajaran IDP tahun terpilih (JPR-05) --}}
    @if ($this->closedIdp)
        <x-card class="space-y-2" wire:loading.remove wire:target="year">
            <h2 class="text-base font-semibold text-navy">{{ __('jp.report.title') }}</h2>
            <p class="text-sm text-muted">{{ __('jp.report.for_year', ['year' => $this->closedIdp->period_year]) }} @if ($this->isOwner){{ __('jp.report.hint') }}@endif</p>
            @if ($this->closedIdp->learningReport)
                <x-button variant="secondary" :href="route('jp.reports.download', $this->closedIdp)">
                    <x-heroicon-o-arrow-down-tray class="size-4" aria-hidden="true" />{{ __('jp.report.download') }}
                </x-button>
            @elseif ($this->isOwner)
                @error('report') <x-error-state :message="$message" /> @enderror
                <x-confirm-dialog :title="__('jp.report.issue_title')" :message="__('jp.report.issue_body')" :confirm="__('jp.report.issue')" action="issueReport">
                    <x-slot:trigger><x-button class="w-full sm:w-auto">{{ __('jp.report.issue') }}</x-button></x-slot:trigger>
                </x-confirm-dialog>
            @else
                <p class="text-sm text-muted">{{ __('jp.report.not_issued') }}</p>
            @endif
        </x-card>
    @endif

    {{-- Unggah sertifikat luar (JPR-02) --}}
    @if ($this->isOwner)
        <x-card class="space-y-3">
            <h2 class="text-base font-semibold text-navy">{{ __('jp.upload.title') }}</h2>
            <p class="text-xs text-muted">{{ __('jp.upload.hint') }}</p>
            <form wire:submit="upload" class="grid gap-3 sm:grid-cols-2">
                <div class="sm:col-span-2"><x-input :label="__('jp.attributes.title')" name="form.title" wire:model="form.title" maxlength="255" required /></div>
                <x-input :label="__('jp.attributes.issuer')" name="form.issuer" wire:model="form.issuer" maxlength="200" required />
                <x-input :label="__('jp.attributes.number')" name="form.number" wire:model="form.number" maxlength="100" />
                <x-select :label="__('jp.attributes.portion')" name="form.portion" wire:model="form.portion" :options="$this->portionOptions()" />
                <x-select :label="__('jp.attributes.mode')" name="form.mode" wire:model="form.mode" :options="$this->modeOptions()" />
                <x-input :label="__('jp.attributes.activity_start')" name="form.activity_start" type="date" wire:model="form.activity_start" required />
                <x-input :label="__('jp.attributes.activity_end')" name="form.activity_end" type="date" wire:model="form.activity_end" required />
                <x-input :label="__('jp.attributes.jp')" name="form.jp" type="number" step="0.1" min="0.1" max="999.9" wire:model="form.jp" :hint="__('jp.upload.jp_hint')" required />
                <div>
                    <x-file-upload :label="__('jp.attributes.file')" name="file" wire:model="file" accept=".pdf,.jpg,.jpeg,.png" :hint="__('jp.upload.file_hint')" />
                    <div wire:loading wire:target="file" class="text-xs text-muted">{{ __('ui.uploading') }}</div>
                </div>
                <div class="sm:col-span-2">
                    <x-button type="submit" class="w-full sm:w-auto" loading="upload">{{ __('jp.upload.submit') }}</x-button>
                </div>
            </form>
        </x-card>
    @endif

    {{-- Riwayat bangkom (JPR-04) --}}
    <section class="space-y-3">
        <h2 class="text-base font-semibold text-navy">{{ __('jp.history.title') }}</h2>
        @if ($this->records->isEmpty())
            <x-empty-state icon="academic-cap" :title="__('jp.history.empty')">
                @if ($this->isOwner)<p class="text-xs text-muted">{{ __('jp.history.empty_hint') }}</p>@endif
            </x-empty-state>
        @else
            <ul class="space-y-3">
                @foreach ($this->records as $record)
                    <li wire:key="r-{{ $record->id }}" class="rounded-lg border border-line bg-surface p-4 shadow-card">
                        <div class="flex flex-wrap items-start justify-between gap-2">
                            <div>
                                <p class="font-medium text-ink">{{ $record->title }}</p>
                                <p class="text-xs text-muted">
                                    {{ $record->activity_start->translatedFormat('j M Y') }}–{{ $record->activity_end->translatedFormat('j M Y') }}
                                    · {{ $record->portion->label() }} · {{ $record->mode->label() }}
                                    @if ($record->certificate) · {{ $record->certificate->issuer }} @endif
                                    @if ($record->sourceable_type) · {{ __('jp.history.automatic') }} @endif
                                </p>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="text-sm font-semibold text-navy">{{ \Illuminate\Support\Number::format((float) $record->jp, maxPrecision: 1, locale: 'id') }} JP</span>
                                <x-badge :variant="$record->status->badge()">{{ $record->status->label() }}</x-badge>
                            </div>
                        </div>
                        @if ($record->reject_reason)
                            <p class="mt-2 text-sm text-danger"><span class="font-medium">{{ __('jp.attributes.reject_reason') }}:</span> {{ $record->reject_reason }}</p>
                        @endif
                        @if ($record->certificate && ! $record->certificate->is_internal)
                            <a href="{{ route('jp.certificates.download', $record) }}" class="mt-2 inline-flex min-h-11 items-center gap-1 text-sm text-teal-700 underline-offset-2 hover:underline">
                                <x-heroicon-o-arrow-down-tray class="size-4" aria-hidden="true" />{{ __('jp.history.download') }}
                            </a>
                        @endif
                    </li>
                @endforeach
            </ul>
        @endif
    </section>
</div>
