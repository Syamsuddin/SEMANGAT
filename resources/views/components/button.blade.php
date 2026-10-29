@props(['variant' => 'primary', 'size' => 'md', 'type' => 'button', 'href' => null, 'loading' => null])
@php
    $base = 'inline-flex min-h-11 items-center justify-center gap-2 rounded-md font-semibold transition-colors duration-150 disabled:cursor-not-allowed disabled:opacity-60';
    $sizes = ['md' => 'px-4 text-sm', 'sm' => 'px-3 text-xs'];
    $variants = [
        'primary' => 'bg-navy text-surface hover:bg-navy-700',
        'secondary' => 'border border-teal-700 bg-surface text-teal-700 hover:bg-teal-50',
        'danger' => 'bg-danger text-surface hover:opacity-90',
        'ghost' => 'text-teal-700 hover:bg-teal-50',
    ];
    $classes = $base.' '.$sizes[$size].' '.$variants[$variant];
@endphp
@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }} @if ($loading) wire:loading.attr="disabled" wire:target="{{ $loading }}" @endif>
        @if ($loading)
            <x-heroicon-o-arrow-path class="size-4 animate-spin" wire:loading wire:target="{{ $loading }}" aria-hidden="true" />
        @endif
        {{ $slot }}
    </button>
@endif
