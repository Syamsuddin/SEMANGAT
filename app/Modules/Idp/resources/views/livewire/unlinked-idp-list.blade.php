<div class="space-y-4">
    <h1 class="text-xl font-semibold text-navy">{{ __('idp.unlinked_list.title') }}</h1>
    <p class="text-sm text-muted">{{ __('idp.unlinked_list.intro') }}</p>

    <div wire:loading.delay><x-skeleton :lines="4" /></div>

    @php($idps = $this->idps)
    @if ($idps === null)
        <x-error-state :message="__('idp.unlinked_list.error')" retry="retry" />
    @elseif ($idps->isEmpty())
        <x-empty-state icon="link" :title="__('idp.unlinked_list.empty_title')">
            <p class="text-xs text-muted">{{ __('idp.unlinked_list.empty_body') }}</p>
        </x-empty-state>
    @else
        <x-table wire:loading.remove>
            <thead class="border-b border-line text-xs text-muted">
                <tr>
                    <th class="px-3 py-2">{{ __('idp.pdf.name') }}</th>
                    <th class="px-3 py-2">{{ __('idp.pdf.position') }}</th>
                    <th class="px-3 py-2">{{ __('idp.pdf.opd') }}</th>
                    <th class="px-3 py-2">{{ __('idp.unlinked_list.status') }}</th>
                    <th class="px-3 py-2 text-right">{{ __('idp.unlinked') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-line">
                @foreach ($idps as $idp)
                    <tr wire:key="u-{{ $idp->id }}">
                        <td class="px-3 py-2">{{ $idp->employee->name }} <span class="block text-xs text-muted">{{ $idp->employee->nip }}</span></td>
                        <td class="px-3 py-2">{{ $idp->employee->position?->name ?? '—' }}</td>
                        <td class="px-3 py-2">{{ $idp->employee->opd?->name ?? '—' }}</td>
                        <td class="px-3 py-2"><x-badge :variant="$idp->status->badge()">{{ $idp->status->label() }}</x-badge></td>
                        <td class="px-3 py-2 text-right">{{ $idp->unlinked_items_count }}</td>
                    </tr>
                @endforeach
            </tbody>
            <x-slot:cards>
                @foreach ($idps as $idp)
                    <li wire:key="uc-{{ $idp->id }}" class="rounded-lg border border-line bg-surface p-4 shadow-card">
                        <p class="font-medium text-ink">{{ $idp->employee->name }}</p>
                        <p class="text-xs text-muted">{{ $idp->employee->position?->name ?? '—' }} · {{ $idp->employee->opd?->name ?? '—' }}</p>
                        <div class="mt-2 flex flex-wrap gap-1">
                            <x-badge :variant="$idp->status->badge()">{{ $idp->status->label() }}</x-badge>
                            <x-badge variant="warning"><x-heroicon-s-link-slash class="size-4" aria-hidden="true" />{{ __('idp.unlinked_list.count', ['count' => $idp->unlinked_items_count]) }}</x-badge>
                        </div>
                    </li>
                @endforeach
            </x-slot:cards>
        </x-table>
        {{ $idps->links() }}
    @endif
</div>
