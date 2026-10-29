@props(['icon' => 'inbox', 'title'])
<div {{ $attributes->merge(['class' => 'flex flex-col items-center gap-3 rounded-lg border border-dashed border-line bg-surface p-6 text-center']) }}>
    <x-dynamic-component :component="'heroicon-o-'.$icon" class="size-6 text-muted" aria-hidden="true" />
    <p class="text-sm text-muted">{{ $title }}</p>
    {{ $slot }}
</div>
