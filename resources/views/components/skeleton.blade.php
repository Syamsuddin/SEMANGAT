@props(['lines' => 3])
<div {{ $attributes->merge(['class' => 'space-y-2']) }} aria-busy="true" aria-label="{{ __('ui.loading') }}">
    @for ($i = 0; $i < $lines; $i++)
        <div class="h-4 animate-pulse rounded-md bg-line {{ $i % 2 ? 'w-2/3' : 'w-full' }}"></div>
    @endfor
</div>
