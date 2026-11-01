@props(['label', 'name', 'hint' => null, 'rows' => 3])
@php($id = $attributes->get('id', str_replace('.', '-', $name)))
<div>
    <label for="{{ $id }}" class="mb-1 block text-sm font-medium text-ink">{{ $label }}</label>
    <textarea id="{{ $id }}" name="{{ $name }}" rows="{{ $rows }}"
              {{ $attributes->except('id')->merge(['class' => 'block w-full rounded-md border bg-surface px-3 py-2 text-sm text-ink '.($errors->has($name) ? 'border-danger' : 'border-line')]) }}
              @error($name) aria-invalid="true" aria-describedby="{{ $id }}-error" @enderror>{{ $slot }}</textarea>
    @if ($hint)
        <p class="mt-1 text-xs text-muted">{{ $hint }}</p>
    @endif
    @error($name)
        <p id="{{ $id }}-error" class="mt-1 text-xs text-danger">{{ $message }}</p>
    @enderror
</div>
