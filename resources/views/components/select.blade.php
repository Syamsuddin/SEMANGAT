@props(['label', 'name', 'options' => [], 'placeholder' => null, 'hint' => null])
@php($id = $attributes->get('id', str_replace('.', '-', $name)))
<div>
    <label for="{{ $id }}" class="mb-1 block text-sm font-medium text-ink">{{ $label }}</label>
    <select id="{{ $id }}" name="{{ $name }}"
            {{ $attributes->except('id')->merge(['class' => 'block min-h-11 w-full rounded-md border bg-surface px-3 text-sm text-ink '.($errors->has($name) ? 'border-danger' : 'border-line')]) }}
            @error($name) aria-invalid="true" aria-describedby="{{ $id }}-error" @enderror>
        @if ($placeholder !== null)
            <option value="">{{ $placeholder }}</option>
        @endif
        @foreach ($options as $value => $text)
            <option value="{{ $value }}">{{ $text }}</option>
        @endforeach
    </select>
    @if ($hint)
        <p class="mt-1 text-xs text-muted">{{ $hint }}</p>
    @endif
    @error($name)
        <p id="{{ $id }}-error" class="mt-1 text-xs text-danger">{{ $message }}</p>
    @enderror
</div>
