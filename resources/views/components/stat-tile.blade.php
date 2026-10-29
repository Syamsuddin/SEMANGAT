@props(['label', 'value', 'href' => null])
<div {{ $attributes->merge(['class' => 'rounded-lg border border-line bg-surface p-4 shadow-card']) }}>
    <p class="text-xs text-muted">{{ $label }}</p>
    <p class="mt-1 text-2xl font-bold text-navy">{{ $value }}</p>
    @if ($href)
        <a href="{{ $href }}" class="mt-2 inline-flex min-h-11 items-center text-sm font-medium text-teal-700 underline-offset-2 hover:underline">{{ $slot }}</a>
    @endif
</div>
