{{-- x-progress: progres IDP/JP (isian orange, jalur line — docs/26). Angka selalu tertulis; warna bukan satu-satunya pembawa info. --}}
@props(['value' => 0, 'max' => 100, 'label'])
@php($pct = $max > 0 ? max(0, min(100, (int) round($value / $max * 100))) : 0)
<div {{ $attributes }}>
    <div class="flex justify-between text-xs text-muted"><span>{{ $label }}</span><span>{{ $pct }}%</span></div>
    <div class="mt-1 h-2 w-full rounded-md bg-line" role="progressbar" aria-label="{{ $label }}" aria-valuenow="{{ $pct }}" aria-valuemin="0" aria-valuemax="100">
        <div class="h-2 rounded-md bg-orange" style="width: {{ $pct }}%"></div>
    </div>
</div>
