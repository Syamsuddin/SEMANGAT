<div class="space-y-4">
    <div>
        <h1 class="text-xl font-semibold text-navy">{{ __('fondasi.home.greeting', ['name' => $user->displayName()]) }}</h1>
        @if ($primaryRole)
            <p class="text-sm text-muted">{{ __('fondasi.home.primary_role', ['role' => $primaryRole->label()]) }}</p>
        @endif
    </div>

    @if ($employee)
        <x-card :title="__('fondasi.home.my_profile')">
            <dl class="grid gap-2 sm:grid-cols-2">
                <div><dt class="text-xs text-muted">{{ __('fondasi.employees.nip') }}</dt><dd>{{ $employee->nip }}</dd></div>
                <div><dt class="text-xs text-muted">{{ __('fondasi.employees.opd') }}</dt><dd>{{ $employee->opd->name }}</dd></div>
                <div class="sm:col-span-2"><dt class="text-xs text-muted">{{ __('fondasi.employees.position') }}</dt><dd>{{ $employee->position?->name ?? __('fondasi.employees.no_position') }}</dd></div>
            </dl>
            <x-button variant="secondary" class="mt-4 w-full sm:w-auto" :href="route('fondasi.profile')">{{ __('fondasi.home.open_profile') }}</x-button>
        </x-card>
    @else
        <x-empty-state icon="identification" :title="__('fondasi.home.no_employee')" />
    @endif

    @if ($jpBalance)
        <x-stat-tile :label="__('jp.overview.balance_label', ['year' => $jpBalance['year']])"
                     :value="__('jp.overview.balance', ['verified' => \Illuminate\Support\Number::format($jpBalance['verified'], maxPrecision: 1, locale: 'id'), 'target' => $jpBalance['target']])"
                     :href="route('jp.records.mine')">{{ $jpBalance['fulfilled'] ? __('jp.overview.fulfilled') : __('jp.overview.not_fulfilled') }} · {{ __('jp.overview.open') }}</x-stat-tile>
    @endif

    @if ($teamCount > 0)
        <x-stat-tile :label="__('fondasi.home.team_label')" :value="$teamCount" :href="route('fondasi.team.index')">{{ __('fondasi.home.open_team') }}</x-stat-tile>
    @endif
</div>
