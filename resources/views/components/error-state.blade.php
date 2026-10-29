@props(['message' => null, 'retry' => null])
<div {{ $attributes->merge(['class' => 'flex flex-col items-center gap-3 rounded-lg border border-danger bg-surface p-6 text-center']) }} role="alert">
    <x-heroicon-o-exclamation-triangle class="size-6 text-danger" aria-hidden="true" />
    <p class="text-sm text-ink">{{ $message ?? __('ui.error_generic') }}</p>
    @if ($retry)
        <x-button variant="secondary" wire:click="{{ $retry }}">{{ __('ui.retry') }}</x-button>
    @endif
</div>
