{{-- x-confirm-dialog: aksi destruktif/irreversibel (docs/26). Slot `trigger` membuka dialog; $action = method Livewire. --}}
@props(['title', 'message', 'confirm', 'action', 'variant' => 'primary'])
<div x-data="{ open: false }" {{ $attributes }}>
    <div x-on:click="open = true">{{ $trigger }}</div>
    <div x-cloak x-show="open" class="fixed inset-0 z-50 flex items-end justify-center bg-ink/50 p-4 sm:items-center" x-on:keydown.escape.window="open = false">
        <div class="w-full max-w-md rounded-lg bg-surface p-4 shadow-card lg:p-6" role="dialog" aria-modal="true" x-trap.noscroll="open">
            <h2 class="text-base font-semibold text-navy">{{ $title }}</h2>
            <p class="mt-2 text-sm text-ink">{{ $message }}</p>
            <div class="mt-4 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                <x-button variant="ghost" x-on:click="open = false">{{ __('ui.cancel') }}</x-button>
                <x-button :variant="$variant" wire:click="{{ $action }}" x-on:click="open = false" :loading="$action">{{ $confirm }}</x-button>
            </div>
        </div>
    </div>
</div>
