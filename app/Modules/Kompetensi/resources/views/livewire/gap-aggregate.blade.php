@php($data = $this->data)
@php($show = fn ($v) => $v === null ? __('kompetensi.aggregate.masked', ['n' => $data['min_n']]) : (is_float($v) ? number_format($v, 1, ',', '.') : number_format($v, 0, ',', '.')))
<div class="space-y-4">
    <h1 class="text-xl font-semibold text-navy">{{ __('kompetensi.aggregate.title') }}</h1>
    <p class="text-sm text-muted">{{ $data['scope'] === 'opd' ? __('kompetensi.aggregate.intro_opd') : __('kompetensi.aggregate.intro_regency') }}</p>
    @if ($data['masked'])
        <p class="text-xs text-muted">{{ __('kompetensi.aggregate.masked_note', ['n' => $data['min_n']]) }}</p>
    @endif

    @if ($data['competencies'] === [])
        <x-empty-state icon="chart-bar" :title="__('kompetensi.aggregate.empty_title')">
            <p class="text-xs text-muted">{{ __('kompetensi.aggregate.empty_body') }}</p>
        </x-empty-state>
    @else
        <x-stat-tile :label="__('kompetensi.aggregate.employees_with_gap')" :value="$show($data['employees'])" class="sm:max-w-xs" />

        <x-card :title="__('kompetensi.aggregate.by_competency')">
            <x-table>
                <thead class="border-b border-line text-xs text-muted">
                    <tr>
                        <th class="px-3 py-2">{{ __('kompetensi.profile.competency') }}</th>
                        <th class="px-3 py-2 text-right">{{ __('kompetensi.aggregate.employees') }}</th>
                        <th class="px-3 py-2 text-right">{{ __('kompetensi.aggregate.avg_gap') }}</th>
                        <th class="px-3 py-2 text-right">{{ __('kompetensi.confidence.verified') }}</th>
                        <th class="px-3 py-2 text-right">{{ __('kompetensi.confidence.provisional') }}</th>
                        <th class="px-3 py-2 text-right">{{ __('kompetensi.confidence.indicative') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @foreach ($data['competencies'] as $row)
                        <tr wire:key="c-{{ $row['code'] }}">
                            <td class="px-3 py-2">{{ $row['name'] }} <span class="text-xs text-muted">{{ $row['code'] }}</span></td>
                            <td class="px-3 py-2 text-right">{{ $show($row['employees']) }}</td>
                            <td class="px-3 py-2 text-right">{{ $show($row['avg_gap']) }}</td>
                            <td class="px-3 py-2 text-right">{{ $show($row['verified']) }}</td>
                            <td class="px-3 py-2 text-right">{{ $show($row['provisional']) }}</td>
                            <td class="px-3 py-2 text-right">{{ $show($row['indicative']) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <x-slot:cards>
                    @foreach ($data['competencies'] as $row)
                        <li wire:key="cc-{{ $row['code'] }}" class="rounded-lg border border-line bg-surface p-4 shadow-card">
                            <p class="font-medium">{{ $row['name'] }}</p>
                            <p class="mt-1 text-sm">{{ __('kompetensi.aggregate.employees') }}: {{ $show($row['employees']) }} · {{ __('kompetensi.aggregate.avg_gap') }}: {{ $show($row['avg_gap']) }}</p>
                            <p class="mt-1 text-xs text-muted">{{ __('kompetensi.confidence.verified') }} {{ $show($row['verified']) }} · {{ __('kompetensi.confidence.provisional') }} {{ $show($row['provisional']) }} · {{ __('kompetensi.confidence.indicative') }} {{ $show($row['indicative']) }}</p>
                        </li>
                    @endforeach
                </x-slot:cards>
            </x-table>
        </x-card>

        <x-card :title="__('kompetensi.aggregate.by_opd')">
            <ul class="divide-y divide-line text-sm">
                @foreach ($data['opds'] as $row)
                    <li wire:key="o-{{ $loop->index }}" class="flex justify-between gap-3 py-2">
                        <span>{{ $row['name'] }}</span>
                        <span class="text-muted">{{ __('kompetensi.aggregate.employees') }} {{ $show($row['employees']) }} · {{ __('kompetensi.aggregate.gaps') }} {{ $show($row['gaps']) }}</span>
                    </li>
                @endforeach
            </ul>
        </x-card>
    @endif
</div>
