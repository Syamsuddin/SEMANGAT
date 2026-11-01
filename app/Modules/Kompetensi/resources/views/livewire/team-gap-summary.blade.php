<div class="space-y-4">
    <h1 class="text-xl font-semibold text-navy">{{ __('kompetensi.team.title') }}</h1>
    <p class="text-sm text-muted">{{ __('kompetensi.team.intro') }}</p>

    @if ($this->members->isEmpty())
        <x-empty-state icon="user-group" :title="__('kompetensi.team.empty_title')">
            <p class="text-xs text-muted">{{ __('kompetensi.team.empty_body') }}</p>
        </x-empty-state>
    @else
        @php($gaps = $this->gaps->groupBy('employee_id'))
        @php($pending = $this->pendingCounts)
        @if ($pending->sum() > 0)
            <x-button variant="secondary" :href="route('kompetensi.validation.index')">{{ __('kompetensi.team.pending_count', ['count' => $pending->sum()]) }}</x-button>
        @endif
        <ul class="space-y-3">
            @foreach ($this->members as $m)
                @php($memberGaps = $gaps->get($m->id, collect()))
                <li wire:key="m-{{ $m->id }}" class="rounded-lg border border-line bg-surface p-4 shadow-card">
                    <a href="{{ route('kompetensi.profile.employee', $m->id) }}" class="flex min-h-11 items-center justify-between gap-3">
                        <span>
                            <span class="block font-medium text-teal-700">{{ $m->name }}</span>
                            <span class="block text-xs text-muted">{{ $m->position?->name ?? __('fondasi.employees.no_position') }}</span>
                        </span>
                        <x-heroicon-o-chevron-right class="size-5 text-muted" aria-hidden="true" />
                    </a>
                    @if ($memberGaps->isEmpty())
                        <p class="mt-2 text-sm text-muted">{{ __('kompetensi.team.no_gap') }}</p>
                    @else
                        <p class="mt-2 text-xs text-muted">{{ __('kompetensi.team.top_gaps') }}</p>
                        <ul class="mt-1 space-y-1">
                            @foreach ($memberGaps->take(3) as $g)
                                <li wire:key="g-{{ $g->id }}" class="flex flex-wrap items-center justify-between gap-2 text-sm">
                                    <span>{{ $g->competency->name }} · {{ __('kompetensi.profile.gap_value', ['gap' => $g->gap]) }}</span>
                                    <x-confidence :status="$g->confidence" />
                                </li>
                            @endforeach
                        </ul>
                    @endif
                    @if (($pending[$m->id] ?? 0) > 0)
                        <p class="mt-2"><x-badge variant="info">{{ __('kompetensi.team.pending_count', ['count' => $pending[$m->id]]) }}</x-badge></p>
                    @endif
                </li>
            @endforeach
        </ul>
    @endif
</div>
