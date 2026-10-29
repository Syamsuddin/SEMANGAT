<div class="space-y-4">
    <div>
        <h1 class="text-xl font-semibold text-navy">{{ $employee->name }}</h1>
        <p class="text-sm text-muted">{{ $employee->nip }} · {{ $employee->employment_type->label() }}</p>
    </div>

    <x-card :title="__('fondasi.employees.employment')">
        <dl class="grid gap-3 sm:grid-cols-2">
            <div><dt class="text-xs text-muted">{{ __('fondasi.employees.status') }}</dt><dd><x-badge :variant="$employee->isActive() ? 'success' : 'neutral'">{{ $employee->status->label() }}</x-badge></dd></div>
            <div><dt class="text-xs text-muted">{{ __('fondasi.employees.opd') }}</dt><dd>{{ $employee->opd->name }}</dd></div>
            <div><dt class="text-xs text-muted">{{ __('fondasi.employees.unit') }}</dt><dd>{{ $employee->orgUnit->name }}</dd></div>
            <div><dt class="text-xs text-muted">{{ __('fondasi.employees.position') }}</dt><dd>{{ $employee->position?->name ?? __('fondasi.employees.no_position') }}</dd></div>
            @if ($employee->position)
                <div><dt class="text-xs text-muted">{{ __('fondasi.employees.position_type') }}</dt><dd>{{ $employee->position->type->label() }}</dd></div>
            @endif
            <div><dt class="text-xs text-muted">{{ __('fondasi.employees.supervisor') }}</dt><dd>{{ $employee->supervisor?->name ?? '—' }}</dd></div>
            @if ($isSelf)
                <div><dt class="text-xs text-muted">{{ __('fondasi.employees.email') }}</dt><dd>{{ $employee->email ?? '—' }}</dd></div>
                <div><dt class="text-xs text-muted">{{ __('fondasi.employees.phone') }}</dt><dd>{{ $employee->phone ?? '—' }}</dd></div>
            @endif
        </dl>
    </x-card>

    <p class="flex items-start gap-2 text-xs text-muted">
        <x-heroicon-o-arrow-path-rounded-square class="size-4 shrink-0" aria-hidden="true" />
        {{ __('fondasi.employees.synced_note', ['date' => $employee->synced_at->timezone(config('app.timezone'))->translatedFormat('j M Y, H.i').' WITA']) }}
    </p>
</div>
