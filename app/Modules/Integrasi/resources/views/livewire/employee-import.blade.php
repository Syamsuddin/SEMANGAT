<div class="space-y-4">
    <h1 class="text-xl font-semibold text-navy">{{ __('integrasi.import.title') }}</h1>

    <x-card>
        <p class="text-sm">{{ __('integrasi.import.intro') }}</p>
        <p class="mt-2 text-xs text-muted">{{ __('integrasi.import.columns') }} <span class="font-mono">{{ implode(', ', $columns) }}</span></p>
        <x-button variant="ghost" size="sm" class="mt-2" :href="route('integrasi.template')">
            <x-heroicon-o-arrow-down-tray class="size-4" aria-hidden="true" /> {{ __('integrasi.import.template') }}
        </x-button>
    </x-card>

    <x-card>
        <div class="space-y-4">
            <x-file-upload name="file" wire:model="file" accept=".csv,.xlsx" :label="__('integrasi.import.file')" :hint="__('integrasi.import.hint')" />
            <div wire:loading wire:target="file" class="text-xs text-muted">{{ __('ui.uploading') }}</div>
            <x-confirm-dialog :title="__('integrasi.import.confirm_title')" :message="__('integrasi.import.confirm_message')" :confirm="__('integrasi.import.run')" action="import">
                <x-slot:trigger><x-button class="w-full sm:w-auto" wire:loading.attr="disabled" wire:target="file">{{ __('integrasi.import.run') }}</x-button></x-slot:trigger>
            </x-confirm-dialog>
        </div>
    </x-card>
</div>
