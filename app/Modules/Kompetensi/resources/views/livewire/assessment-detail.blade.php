<div class="space-y-4">
    <div>
        <h1 class="text-xl font-semibold text-navy">{{ __('kompetensi.detail.title') }}</h1>
        <p class="text-sm text-muted">{{ $assessment->employee->name }} · {{ $assessment->competency->name }}</p>
    </div>

    <x-card>
        <dl class="grid gap-3 sm:grid-cols-2">
            <div><dt class="text-xs text-muted">{{ __('kompetensi.detail.method') }}</dt><dd>{{ $assessment->method->label() }}</dd></div>
            <div><dt class="text-xs text-muted">{{ __('kompetensi.profile.actual') }}</dt><dd class="flex items-center gap-2">{{ __('kompetensi.profile.level', ['level' => $assessment->level]) }} <x-confidence :status="$assessment->method->confidence()" /></dd></div>
            <div><dt class="text-xs text-muted">{{ __('kompetensi.detail.institution') }}</dt><dd>{{ $assessment->institution ?? '—' }}</dd></div>
            <div><dt class="text-xs text-muted">{{ __('kompetensi.detail.assessed_on') }}</dt><dd>{{ $assessment->assessed_on->translatedFormat('j M Y') }}</dd></div>
            <div><dt class="text-xs text-muted">{{ __('kompetensi.detail.valid_until') }}</dt><dd>{{ $assessment->valid_until?->translatedFormat('j M Y') ?? '—' }}</dd></div>
        </dl>
    </x-card>

    @if (! $unlocked)
        <x-card :title="__('kompetensi.detail.reason_title')">
            <p class="mb-3 text-sm">{{ __('kompetensi.detail.reason_body') }}</p>
            <form wire:submit="open" class="space-y-3">
                <x-input :label="__('kompetensi.detail.reason')" name="reason" wire:model="reason" maxlength="255" required />
                <x-button type="submit" class="w-full sm:w-auto" loading="open">{{ __('kompetensi.detail.open') }}</x-button>
            </form>
        </x-card>
    @else
        <x-card :title="__('kompetensi.detail.notes')">
            <p class="whitespace-pre-line text-sm">{{ $assessment->notes ?? __('kompetensi.detail.no_notes') }}</p>
        </x-card>

        <x-card :title="__('kompetensi.detail.report_file')">
            @if ($assessment->report_file_path === null)
                <x-empty-state icon="document" :title="__('kompetensi.detail.no_file')" />
            @else
                <x-button variant="secondary" wire:click="download" loading="download">
                    <x-heroicon-o-arrow-down-tray class="size-4" aria-hidden="true" /> {{ __('kompetensi.detail.download') }}
                </x-button>
            @endif

            @can('attachReport', $assessment)
                <form wire:submit="upload" class="mt-4 space-y-3 border-t border-line pt-4">
                    <x-file-upload name="file" wire:model="file" accept=".pdf,.jpg,.jpeg,.png" :label="$assessment->report_file_path ? __('kompetensi.detail.replace') : __('kompetensi.detail.upload')" :hint="__('kompetensi.detail.upload_hint')" />
                    <div wire:loading wire:target="file" class="text-xs text-muted">{{ __('ui.uploading') }}</div>
                    <x-button type="submit" class="w-full sm:w-auto" loading="upload">{{ __('kompetensi.detail.upload') }}</x-button>
                </form>
            @endcan
        </x-card>
    @endif
</div>
