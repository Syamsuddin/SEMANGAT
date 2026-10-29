@props(['variant' => 'neutral'])
@php
    $variants = [
        'neutral' => 'border-line bg-surface text-muted',
        'info' => 'border-info bg-surface text-info',
        'success' => 'border-success bg-surface text-success',
        'warning' => 'border-orange bg-orange-50 text-warning',
        'danger' => 'border-danger bg-surface text-danger',
    ];
@endphp
<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1 rounded-md border px-2 py-0.5 text-xs font-medium '.$variants[$variant]]) }}>{{ $slot }}</span>
