{{-- x-confidence: label keyakinan KOM-05 (docs/26) — dibedakan pola border + ikon + teks, bukan warna saja. --}}
@props(['status'])
@php
    $status = $status instanceof \App\Support\Enums\Confidence ? $status : \App\Support\Enums\Confidence::from($status);
    $styles = [
        'verified' => ['class' => 'border-solid border-teal bg-teal-50 text-teal-700', 'icon' => 'heroicon-s-check-badge'],
        'provisional' => ['class' => 'border-dashed border-orange bg-orange-50 text-ink', 'icon' => 'heroicon-s-clock'],
        'indicative' => ['class' => 'border-dotted border-muted bg-surface italic text-muted', 'icon' => 'heroicon-s-light-bulb'],
    ][$status->value];
@endphp
<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1 rounded-md border px-2 py-0.5 text-xs font-medium '.$styles['class']]) }}
      data-confidence="{{ $status->value }}" title="{{ __('kompetensi.confidence.'.$status->value.'_hint') }}">
    <x-dynamic-component :component="$styles['icon']" class="size-4 shrink-0" aria-hidden="true" />
    {{ $status->label() }}
</span>
