@php($data = $this->data)
@php($show = fn ($v, ?string $mask = null) => $v === null
    ? ($mask === \App\Modules\JpRiwayat\Services\JpRecap::MASK_COMPLEMENT ? __('jp.recap.masked_complement') : __('jp.recap.masked', ['n' => $data['min_n']]))
    : (is_float($v) ? number_format($v, 1, ',', '.') : number_format($v, 0, ',', '.')))
<div class="space-y-4">
    <div class="flex flex-wrap items-center justify-between gap-2">
        <h1 class="text-xl font-semibold text-navy">{{ __('jp.recap.title') }}</h1>
        <div class="w-36"><x-select :label="__('jp.overview.year')" name="year" wire:model.live="year" :options="$this->yearOptions()" /></div>
    </div>
    <p class="text-sm text-muted">{{ $data['scope'] === 'opd' ? __('jp.recap.intro_opd', ['target' => $data['target']]) : __('jp.recap.intro_regency', ['target' => $data['target']]) }}</p>
    @if ($data['masked'])
        <p class="text-xs text-muted">{{ __('jp.recap.masked_note', ['n' => $data['min_n']]) }}</p>
    @endif

    <div wire:loading.delay wire:target="year"><x-skeleton :lines="4" /></div>
    <div class="space-y-4" wire:loading.remove wire:target="year">
        @if ($data['opds'] === [])
            <x-empty-state icon="chart-bar" :title="__('jp.recap.empty')" />
        @else
            <div class="grid gap-3 sm:grid-cols-3">
                <x-stat-tile :label="__('jp.recap.employees')" :value="$show($data['total']['employees'], $data['total']['mask'])" />
                <x-stat-tile :label="__('jp.recap.fulfilled', ['target' => $data['target']])" :value="$show($data['total']['fulfilled'], $data['total']['mask'])" />
                <x-stat-tile :label="__('jp.recap.avg_jp')" :value="$show($data['total']['avg_jp'], $data['total']['mask'])" />
            </div>

            <x-card :title="__('jp.recap.by_opd')">
                <x-table>
                    <thead class="border-b border-line text-xs text-muted">
                        <tr>
                            <th class="px-3 py-2">{{ __('jp.recap.opd') }}</th>
                            <th class="px-3 py-2 text-right">{{ __('jp.recap.employees') }}</th>
                            <th class="px-3 py-2 text-right">{{ __('jp.recap.fulfilled', ['target' => $data['target']]) }}</th>
                            <th class="px-3 py-2 text-right">{{ __('jp.recap.avg_jp') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line">
                        @foreach ($data['opds'] as $row)
                            <tr wire:key="o-{{ $loop->index }}">
                                <td class="px-3 py-2">{{ $row['name'] }}</td>
                                <td class="px-3 py-2 text-right">{{ $show($row['employees'], $row['mask']) }}</td>
                                <td class="px-3 py-2 text-right">{{ $show($row['fulfilled'], $row['mask']) }}</td>
                                <td class="px-3 py-2 text-right">{{ $show($row['avg_jp'], $row['mask']) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <x-slot:cards>
                        @foreach ($data['opds'] as $row)
                            <li wire:key="oc-{{ $loop->index }}" class="rounded-lg border border-line bg-surface p-4 text-sm shadow-card">
                                <p class="font-medium text-ink">{{ $row['name'] }}</p>
                                <p class="text-xs text-muted">{{ __('jp.recap.employees') }}: {{ $show($row['employees'], $row['mask']) }} · {{ __('jp.recap.fulfilled', ['target' => $data['target']]) }}: {{ $show($row['fulfilled'], $row['mask']) }} · {{ __('jp.recap.avg_jp') }}: {{ $show($row['avg_jp'], $row['mask']) }}</p>
                            </li>
                        @endforeach
                    </x-slot:cards>
                </x-table>
            </x-card>

            @if ($data['employees'] !== [])
                <x-card :title="__('jp.recap.by_employee')">
                    <ul class="divide-y divide-line">
                        @foreach ($data['employees'] as $row)
                            <li wire:key="e-{{ $row['nip'] }}" class="flex flex-wrap items-center justify-between gap-2 py-2 text-sm">
                                <span>{{ $row['name'] }} <span class="text-xs text-muted">{{ $row['nip'] }}</span></span>
                                <span class="flex items-center gap-2">
                                    {{ number_format($row['jp'], 1, ',', '.') }} / {{ $data['target'] }} JP
                                    @if ($row['fulfilled'])
                                        <x-badge variant="success"><x-heroicon-s-check-badge class="size-4" aria-hidden="true" />{{ __('jp.overview.fulfilled') }}</x-badge>
                                    @else
                                        <x-badge variant="warning"><x-heroicon-s-clock class="size-4" aria-hidden="true" />{{ __('jp.overview.not_fulfilled') }}</x-badge>
                                    @endif
                                </span>
                            </li>
                        @endforeach
                    </ul>
                </x-card>
            @endif
        @endif
    </div>
</div>
