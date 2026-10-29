@props(['title' => null])
<section {{ $attributes->merge(['class' => 'rounded-lg border border-line bg-surface p-4 shadow-card lg:p-6']) }}>
    @if ($title)
        <h2 class="mb-3 text-base font-semibold text-navy">{{ $title }}</h2>
    @endif
    {{ $slot }}
</section>
