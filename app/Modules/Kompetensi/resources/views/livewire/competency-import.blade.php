<div class="space-y-4">
    <h1 class="text-xl font-semibold text-navy">{{ __('kompetensi.import.title') }}</h1>

    <x-card>
        <p class="text-sm">{{ __('kompetensi.import.intro') }}</p>
        <div class="mt-4 space-y-4">
            <x-select :label="__('kompetensi.import.kind')" name="kind" wire:model.live="kind" :options="__('kompetensi.import.kinds')" />
            <p class="text-xs text-muted">{{ __('kompetensi.import.columns') }} <span class="font-mono">{{ implode(', ', $columns) }}</span></p>
            <x-file-upload name="file" wire:model="file" accept=".csv,.xlsx" :label="__('kompetensi.import.file')" />
            <div wire:loading wire:target="file" class="text-xs text-muted">{{ __('ui.uploading') }}</div>
            <x-confirm-dialog :title="__('kompetensi.import.confirm_title')" :message="__('kompetensi.import.confirm_body')" :confirm="__('kompetensi.import.submit')" action="import">
                <x-slot:trigger><x-button class="w-full sm:w-auto" wire:loading.attr="disabled" wire:target="file,import">{{ __('kompetensi.import.submit') }}</x-button></x-slot:trigger>
            </x-confirm-dialog>
        </div>
    </x-card>

    <div wire:loading.delay wire:target="import"><x-skeleton :lines="3" /></div>

    @if ($report !== null)
        <div wire:loading.remove wire:target="import">
            @if ($report['error'] !== null)
                <x-error-state :message="$report['error']" />
            @elseif ($report['errors'] !== [])
                <x-card>
                    <div role="alert">
                        <p class="text-sm font-medium text-danger">{{ __('kompetensi.import.rejected', ['count' => count($report['errors'])]) }}</p>
                        <ul class="mt-2 divide-y divide-line text-sm">
                            @foreach ($report['errors'] as $row => $messages)
                                <li wire:key="e-{{ $row }}" class="py-2"><span class="font-medium">{{ __('kompetensi.import.row', ['row' => $row]) }}:</span> {{ implode('; ', $messages) }}</li>
                            @endforeach
                        </ul>
                    </div>
                </x-card>
            @else
                <p class="rounded-md border border-success bg-surface p-3 text-sm text-success" role="status">{{ __('kompetensi.import.done', ['count' => $report['rows_ok']]) }}</p>
            @endif
        </div>
    @endif
</div>
