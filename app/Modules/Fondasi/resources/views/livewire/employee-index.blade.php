<div class="space-y-4">
    <h1 class="text-xl font-semibold text-navy">{{ __('fondasi.employees.title') }}</h1>

    <div class="grid gap-3 sm:grid-cols-3">
        <div class="sm:col-span-2">
            <x-input name="search" :label="__('fondasi.employees.search')" wire:model.live.debounce.400ms="search" type="search" />
        </div>
        <div>
            <label for="status" class="mb-1 block text-sm font-medium">{{ __('fondasi.employees.status') }}</label>
            <select id="status" wire:model.live="status" class="block min-h-11 w-full rounded-md border border-line bg-surface px-3 text-sm">
                <option value="active">{{ __('integrasi.employee_status.active') }}</option>
                <option value="inactive">{{ __('integrasi.employee_status.inactive') }}</option>
                <option value="all">{{ __('fondasi.employees.all') }}</option>
            </select>
        </div>
    </div>

    <div wire:loading.delay wire:target="search,status,gotoPage,nextPage,previousPage"><x-skeleton :lines="4" /></div>

    <div wire:loading.remove wire:target="search,status,gotoPage,nextPage,previousPage">
        @if ($this->employees->isEmpty())
            <x-empty-state icon="users" :title="__('fondasi.employees.empty')" />
        @else
            <x-table>
                <thead class="border-b border-line text-xs text-muted">
                    <tr><th class="p-3">{{ __('fondasi.employees.name') }}</th><th class="p-3">{{ __('fondasi.employees.nip') }}</th><th class="p-3">{{ __('fondasi.employees.opd') }}</th><th class="p-3">{{ __('fondasi.employees.status') }}</th></tr>
                </thead>
                <tbody>
                    @foreach ($this->employees as $e)
                        <tr class="border-b border-line last:border-0" wire:key="row-{{ $e->id }}">
                            <td class="p-3"><a class="font-medium text-teal-700 hover:underline" href="{{ route('fondasi.employees.show', $e) }}">{{ $e->name }}</a><p class="text-xs text-muted">{{ $e->position?->name }}</p></td>
                            <td class="p-3">{{ $e->nip }}</td>
                            <td class="p-3">{{ $e->opd->name }}</td>
                            <td class="p-3"><x-badge :variant="$e->isActive() ? 'success' : 'neutral'">{{ $e->status->label() }}</x-badge></td>
                        </tr>
                    @endforeach
                </tbody>
                <x-slot:cards>
                    @foreach ($this->employees as $e)
                        <li wire:key="card-{{ $e->id }}">
                            <a href="{{ route('fondasi.employees.show', $e) }}" class="block rounded-lg border border-line bg-surface p-4 shadow-card">
                                <p class="font-medium text-teal-700">{{ $e->name }}</p>
                                <p class="text-xs text-muted">{{ $e->nip }} · {{ $e->opd->name }}</p>
                                <x-badge class="mt-2" :variant="$e->isActive() ? 'success' : 'neutral'">{{ $e->status->label() }}</x-badge>
                            </a>
                        </li>
                    @endforeach
                </x-slot:cards>
            </x-table>
            <div class="mt-4">{{ $this->employees->links() }}</div>
        @endif
    </div>
</div>
