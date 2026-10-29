@props(['label', 'name', 'hint' => null, 'accept' => null])
<div>
    <label for="{{ $name }}" class="mb-1 block text-sm font-medium text-ink">{{ $label }}</label>
    <input id="{{ $name }}" type="file" @if ($accept) accept="{{ $accept }}" @endif
           {{ $attributes->merge(['class' => 'block min-h-11 w-full rounded-md border border-line bg-surface p-2 text-sm text-ink file:mr-3 file:min-h-9 file:rounded-md file:border-0 file:bg-teal-50 file:px-3 file:text-sm file:font-medium file:text-teal-700']) }}
           @error($name) aria-invalid="true" aria-describedby="{{ $name }}-error" @enderror>
    @if ($hint)
        <p class="mt-1 text-xs text-muted">{{ $hint }}</p>
    @endif
    @error($name)
        <p id="{{ $name }}-error" class="mt-1 text-xs text-danger">{{ $message }}</p>
    @enderror
</div>
