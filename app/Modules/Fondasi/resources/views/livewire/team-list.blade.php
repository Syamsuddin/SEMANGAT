<div class="space-y-4">
    <h1 class="text-xl font-semibold text-navy">{{ __('fondasi.team.title') }}</h1>
    <p class="text-sm text-muted">{{ __('fondasi.team.subtitle') }}</p>

    @if ($this->members->isEmpty())
        <x-empty-state icon="user-group" :title="__('fondasi.team.empty')" />
    @else
        <ul class="space-y-3">
            @foreach ($this->members as $m)
                <li wire:key="m-{{ $m->id }}">
                    <a href="{{ route('fondasi.employees.show', $m) }}" class="flex min-h-11 items-center justify-between gap-3 rounded-lg border border-line bg-surface p-4 shadow-card">
                        <span>
                            <span class="block font-medium text-teal-700">{{ $m->name }}</span>
                            <span class="block text-xs text-muted">{{ $m->position?->name ?? __('fondasi.employees.no_position') }}</span>
                        </span>
                        <x-heroicon-o-chevron-right class="size-5 text-muted" aria-hidden="true" />
                    </a>
                </li>
            @endforeach
        </ul>
    @endif
</div>
