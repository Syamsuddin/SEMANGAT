<div class="space-y-4">
    <div>
        <h1 class="text-xl font-semibold text-navy">{{ $isSelf ? __('kompetensi.profile.title') : __('kompetensi.profile.title_other', ['name' => $employee->name]) }}</h1>
        <p class="text-sm text-muted">{{ __('kompetensi.profile.position') }}: {{ $employee->position?->name ?? '—' }}</p>
    </div>

    @if ($employee->position === null)
        <x-empty-state icon="briefcase" :title="__('kompetensi.profile.no_position')" />
    @elseif ($standard === null)
        <x-empty-state icon="clipboard-document-list" :title="__('kompetensi.profile.no_skj_title')">
            <p class="text-xs text-muted">{{ __('kompetensi.profile.no_skj_body') }}</p>
        </x-empty-state>
    @else
        @php($gapCount = collect($lines)->filter(fn ($l) => $l->hasGap())->count())
        <x-card>
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-xs text-muted"><abbr title="{{ __('kompetensi.profile.skj_hint') }}" class="no-underline">{{ __('kompetensi.profile.skj') }}</abbr>: {{ $standard->name }}</p>
                    <p class="mt-1 text-base font-semibold text-navy">
                        {{ $gapCount > 0 ? __('kompetensi.profile.gaps_count', ['count' => $gapCount]) : __('kompetensi.profile.all_met') }}
                    </p>
                </div>
                @if ($isSelf)
                    <x-button variant="secondary" :href="route('kompetensi.self.create')">{{ __('kompetensi.profile.self_cta') }}</x-button>
                @endif
            </div>
        </x-card>

        <ul class="space-y-3" aria-label="{{ __('kompetensi.profile.skj') }}">
            @foreach ($lines as $line)
                @php($competency = $competencies[$line->competencyId] ?? null)
                <li wire:key="line-{{ $line->competencyId }}" class="rounded-lg border border-line bg-surface p-4 shadow-card">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="font-medium text-ink">{{ $competency?->name }}</p>
                            <p class="text-xs text-muted">{{ $competency?->code }} · {{ $competency?->type->label() }}</p>
                        </div>
                        <x-confidence :status="$line->confidence" />
                    </div>
                    <dl class="mt-3 grid grid-cols-3 gap-2 text-sm">
                        <div><dt class="text-xs text-muted">{{ __('kompetensi.profile.required') }}</dt><dd>{{ __('kompetensi.profile.level', ['level' => $line->requiredLevel]) }}</dd></div>
                        <div>
                            <dt class="text-xs text-muted">{{ __('kompetensi.profile.actual') }}</dt>
                            <dd @class(['font-bold text-navy' => $line->confidence === \App\Support\Enums\Confidence::Verified, 'text-muted italic' => $line->actualLevel === null])>
                                {{ $line->actualLevel === null ? __('kompetensi.profile.no_data') : __('kompetensi.profile.level', ['level' => $line->actualLevel]) }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-xs text-muted">{{ __('kompetensi.profile.gap') }}</dt>
                            <dd>
                                @if ($line->hasGap())
                                    <x-badge variant="warning">{{ __('kompetensi.profile.gap_value', ['gap' => $line->gap]) }}</x-badge>
                                @else
                                    <x-badge variant="success"><x-heroicon-s-check class="size-4" aria-hidden="true" />{{ __('kompetensi.profile.met') }}</x-badge>
                                @endif
                            </dd>
                        </div>
                    </dl>
                </li>
            @endforeach
        </ul>
    @endif

    <x-card :title="__('kompetensi.profile.assessments')">
        @if ($assessments->isEmpty())
            <p class="text-sm text-muted">{{ __('kompetensi.profile.no_assessments') }}</p>
        @else
            <ul class="divide-y divide-line">
                @foreach ($assessments as $a)
                    <li wire:key="a-{{ $a->id }}" class="flex flex-col gap-1 py-3 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p class="text-sm font-medium text-ink">{{ $a->competency->name }} — {{ __('kompetensi.profile.level', ['level' => $a->level]) }}</p>
                            <p class="text-xs text-muted">{{ $a->method->label() }} · {{ $a->assessed_on->translatedFormat('j M Y') }}
                                @if ($a->valid_until) · {{ __('kompetensi.profile.valid_until', ['date' => $a->valid_until->translatedFormat('j M Y')]) }} @endif
                            </p>
                            @if ($a->rejected_at && $a->rejection_note)
                                <p class="text-xs text-danger">{{ __('kompetensi.detail.rejection_note') }}: {{ $a->rejection_note }}</p>
                            @endif
                        </div>
                        <div class="flex flex-wrap items-center gap-2">
                            @if ($a->isPending())
                                <x-badge variant="info">{{ __('kompetensi.profile.pending') }}</x-badge>
                            @elseif ($a->rejected_at)
                                <x-badge variant="danger">{{ __('kompetensi.profile.rejected') }}</x-badge>
                            @elseif ($a->isExpired())
                                <x-badge>{{ __('kompetensi.profile.expired') }}</x-badge>
                            @else
                                <x-confidence :status="$a->method->confidence()" />
                            @endif
                            @if (! $a->isSelfAssessment())
                                @can('viewDetail', $a)
                                    <a href="{{ route('kompetensi.assessments.show', $a->id) }}" class="inline-flex min-h-11 items-center text-sm font-medium text-teal-700 underline-offset-2 hover:underline">{{ __('kompetensi.profile.assessment_detail') }}</a>
                                @endcan
                            @endif
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </x-card>

    @if ($isSelf)
        <x-card :title="__('kompetensi.profile.access_log')">
            <p class="mb-3 text-xs text-muted">{{ __('kompetensi.profile.access_log_hint') }}</p>
            @if ($accessLog->isEmpty())
                <p class="text-sm text-muted">{{ __('kompetensi.profile.access_log_empty') }}</p>
            @else
                <ul class="divide-y divide-line text-sm">
                    @foreach ($accessLog as $log)
                        <li wire:key="log-{{ $log->id }}" class="py-2">
                            <span class="font-medium">{{ $log->user?->displayName() ?? '—' }}</span>
                            {{ $log->action->value === 'export' ? __('kompetensi.profile.downloaded') : __('kompetensi.profile.opened') }}
                            · {{ $log->created_at->timezone(config('app.timezone'))->translatedFormat('j M Y, H.i') }} WITA
                            @if ($log->reason) <span class="block text-xs text-muted">{{ $log->reason }}</span> @endif
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-card>
    @endif
</div>
