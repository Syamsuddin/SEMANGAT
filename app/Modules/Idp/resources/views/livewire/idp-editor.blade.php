<div class="space-y-4">
    @php($idp = $this->idp)
    <div class="flex flex-wrap items-center justify-between gap-2">
        <div>
            <h1 class="flex items-center gap-1 text-xl font-semibold text-navy">
                {{ __('idp.editor.title') }}
                <span title="{{ __('idp.term_hint') }}"><x-heroicon-o-information-circle class="size-5 text-muted" aria-label="{{ __('idp.term_hint') }}" /></span>
            </h1>
            <p class="text-sm text-muted">{{ __('idp.editor.period', ['year' => $idp?->period_year ?? now()->year]) }}</p>
        </div>
        @if ($idp)
            <x-badge :variant="$idp->status->badge()">{{ $idp->status->label() }}</x-badge>
        @endif
    </div>

    <div wire:loading.delay wire:target="start,submit,publish,saveCheckin,saveReflection,issueReport"><x-skeleton :lines="4" /></div>

    @error('items') <x-error-state :message="$message" /> @enderror

    @if ($idp === null)
        <x-empty-state icon="clipboard-document-list" :title="__('idp.editor.empty_title')">
            <p class="text-xs text-muted">{{ __('idp.editor.empty_body') }}</p>
            <x-button wire:click="start" loading="start">{{ __('idp.editor.start') }}</x-button>
        </x-empty-state>
    @else
        <div class="space-y-4" wire:loading.remove wire:target="start,submit,publish">
            @if ($idp->isEditable() && $idp->supervisor_note)
                <div class="rounded-lg border border-dashed border-orange bg-orange-50 p-4 text-sm text-ink" role="status">
                    <p class="font-medium">{{ __('idp.editor.returned_note') }}</p>
                    <p class="mt-1">{{ $idp->supervisor_note }}</p>
                </div>
            @endif

            {{-- 1. Kompetensi prioritas --}}
            <section class="space-y-3">
                <h2 class="text-base font-semibold text-navy">{{ __('idp.editor.step_items') }}</h2>
                @if ($idp->isEditable())
                    <p class="text-sm text-muted">{{ __('idp.editor.items_hint') }}</p>
                    @if ($this->workResultOptions === [])
                        <p class="text-xs text-warning">{{ __('idp.editor.no_work_results') }}</p>
                    @endif
                @endif

                @foreach ($idp->items as $item)
                    <x-card wire:key="item-{{ $item->id }}" class="space-y-3">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <p class="font-medium text-ink">{{ $item->position }}. {{ $item->competency->name }}</p>
                            <div class="flex flex-wrap gap-1">
                                @if ($item->gap)
                                    <x-confidence :status="$item->gap->confidence" />
                                @endif
                                @unless ($item->isLinked())
                                    <x-badge variant="warning" title="{{ __('idp.unlinked_hint') }}">
                                        <x-heroicon-s-link-slash class="size-4" aria-hidden="true" />{{ __('idp.unlinked') }}
                                    </x-badge>
                                @endunless
                            </div>
                        </div>

                        @if ($idp->isEditable())
                            @if ($item->gap_id)
                                <p class="text-xs text-muted">{{ __('idp.editor.suggested') }}</p>
                            @endif
                            <x-select :label="__('idp.editor.item_competency')" name="items.{{ $item->id }}.competency_id" wire:model="items.{{ $item->id }}.competency_id" :options="$this->competencyOptions" />
                            <div class="grid grid-cols-2 gap-3">
                                <x-input :label="__('idp.attributes.start_level')" name="items.{{ $item->id }}.start_level" type="number" min="0" max="5" wire:model="items.{{ $item->id }}.start_level" />
                                <x-input :label="__('idp.attributes.target_level')" name="items.{{ $item->id }}.target_level" type="number" min="1" max="5" wire:model="items.{{ $item->id }}.target_level" />
                            </div>
                            <x-select :label="__('idp.editor.work_result')" name="items.{{ $item->id }}.skp_work_result_id" wire:model="items.{{ $item->id }}.skp_work_result_id" :options="$this->workResultOptions" :placeholder="__('idp.editor.no_work_result')" />
                            <x-textarea :label="__('idp.attributes.success_indicator')" name="items.{{ $item->id }}.success_indicator" wire:model="items.{{ $item->id }}.success_indicator" maxlength="2000" />
                            <x-input :label="__('idp.attributes.mentor_nip')" name="items.{{ $item->id }}.mentor_nip" inputmode="numeric" maxlength="18" wire:model="items.{{ $item->id }}.mentor_nip" />
                            <div class="flex flex-col gap-2 sm:flex-row sm:justify-end">
                                <x-confirm-dialog :title="__('idp.editor.remove_item_title')" :message="__('idp.editor.remove_item_body')"
                                                  :confirm="__('idp.editor.remove_item')" action="removeItem({{ $item->id }})" variant="danger">
                                    <x-slot:trigger><x-button variant="ghost" class="w-full sm:w-auto">{{ __('idp.editor.remove_item') }}</x-button></x-slot:trigger>
                                </x-confirm-dialog>
                                <x-button variant="secondary" class="w-full sm:w-auto" wire:click="saveItem({{ $item->id }})" loading="saveItem({{ $item->id }})">{{ __('idp.save') }}</x-button>
                            </div>
                        @else
                            <dl class="space-y-1 text-sm">
                                <div><dt class="inline text-muted">{{ __('idp.editor.levels') }}:</dt> <dd class="inline">{{ __('idp.editor.level_range', ['start' => $item->start_level, 'target' => $item->target_level]) }}</dd></div>
                                <div><dt class="inline text-muted">{{ __('idp.editor.work_result') }}:</dt> <dd class="inline">{{ $item->workResult ? $item->workResult->code.' — '.$item->workResult->description : '—' }}</dd></div>
                                @if ($item->success_indicator)
                                    <div><dt class="inline text-muted">{{ __('idp.attributes.success_indicator') }}:</dt> <dd class="inline">{{ $item->success_indicator }}</dd></div>
                                @endif
                                @if ($item->mentor)
                                    <div><dt class="inline text-muted">{{ __('idp.attributes.mentor_employee_id') }}:</dt> <dd class="inline">{{ $item->mentor->name }}</dd></div>
                                @endif
                            </dl>
                        @endif

                        {{-- 2. Aktivitas 10:20:70 per butir --}}
                        <div class="space-y-2 border-t border-line pt-3">
                            <p class="text-sm font-medium text-ink">{{ __('idp.editor.step_activities') }}</p>
                            @forelse ($item->activities as $activity)
                                <div wire:key="act-{{ $activity->id }}" class="flex items-start justify-between gap-2 text-sm">
                                    <div>
                                        <x-badge>{{ $activity->portion->label() }}</x-badge>
                                        <span class="ml-1">{{ $activity->title }}</span>
                                        <span class="text-xs text-muted">· {{ $activity->planned_jp }} JP
                                            @if ($activity->planned_start) · {{ $activity->planned_start->translatedFormat('j M Y') }} – {{ $activity->planned_end?->translatedFormat('j M Y') ?? '…' }} @endif
                                        </span>
                                    </div>
                                    @if ($idp->isEditable())
                                        <x-button variant="ghost" size="sm" wire:click="removeActivity({{ $activity->id }})" aria-label="{{ __('idp.editor.remove_activity') }}">
                                            <x-heroicon-o-trash class="size-4" aria-hidden="true" />
                                        </x-button>
                                    @endif
                                </div>
                            @empty
                                <p class="text-xs text-muted">{{ __('idp.editor.no_activities') }}</p>
                            @endforelse

                            @if ($idp->isEditable())
                                <div class="grid gap-2 sm:grid-cols-2">
                                    <x-select :label="__('idp.attributes.portion')" name="activity.{{ $item->id }}.portion" wire:model="activity.{{ $item->id }}.portion" :options="$this->portionOptions()" :placeholder="__('idp.editor.choose')" />
                                    <x-input :label="__('idp.attributes.title')" name="activity.{{ $item->id }}.title" maxlength="200" wire:model="activity.{{ $item->id }}.title" />
                                    <x-input :label="__('idp.attributes.planned_jp')" name="activity.{{ $item->id }}.planned_jp" type="number" min="0" wire:model="activity.{{ $item->id }}.planned_jp" />
                                    <div class="grid grid-cols-2 gap-2">
                                        <x-input :label="__('idp.attributes.planned_start')" name="activity.{{ $item->id }}.planned_start" type="date" wire:model="activity.{{ $item->id }}.planned_start" />
                                        <x-input :label="__('idp.attributes.planned_end')" name="activity.{{ $item->id }}.planned_end" type="date" wire:model="activity.{{ $item->id }}.planned_end" />
                                    </div>
                                </div>
                                <x-button variant="secondary" size="sm" wire:click="addActivity({{ $item->id }})" loading="addActivity({{ $item->id }})">{{ __('idp.editor.add_activity') }}</x-button>
                            @endif
                        </div>
                    </x-card>
                @endforeach

                @if ($idp->isEditable() && $idp->items->count() < \App\Modules\Idp\Models\Idp::MAX_ITEMS)
                    <x-card class="space-y-3">
                        <p class="font-medium text-ink">{{ __('idp.editor.add_item') }}</p>
                        <x-select :label="__('idp.editor.item_competency')" name="newItem.competency_id" wire:model="newItem.competency_id" :options="$this->competencyOptions" :placeholder="__('idp.editor.choose')" />
                        <div class="grid grid-cols-2 gap-3">
                            <x-input :label="__('idp.attributes.start_level')" name="newItem.start_level" type="number" min="0" max="5" wire:model="newItem.start_level" />
                            <x-input :label="__('idp.attributes.target_level')" name="newItem.target_level" type="number" min="1" max="5" wire:model="newItem.target_level" />
                        </div>
                        <x-select :label="__('idp.editor.work_result')" name="newItem.skp_work_result_id" wire:model="newItem.skp_work_result_id" :options="$this->workResultOptions" :placeholder="__('idp.editor.no_work_result')" />
                        <x-button variant="secondary" wire:click="addItem" loading="addItem">{{ __('idp.editor.add_item') }}</x-button>
                    </x-card>
                @endif
            </section>

            {{-- 3. Ajukan --}}
            @if ($idp->isEditable())
                <section class="space-y-3">
                    <h2 class="text-base font-semibold text-navy">{{ __('idp.editor.step_submit') }}</h2>
                    <x-card class="space-y-3">
                        <x-textarea :label="__('idp.attributes.career_aspiration')" name="form.career_aspiration" wire:model="form.career_aspiration" maxlength="2000" />
                        <x-input :label="__('idp.attributes.target_jp')" name="form.target_jp" type="number" min="0" wire:model="form.target_jp"
                                 :hint="__('idp.editor.jp_planned', ['planned' => $idp->items->flatMap->activities->sum('planned_jp'), 'target' => $idp->target_jp])" />
                        <div class="flex flex-col gap-2 sm:flex-row sm:justify-end">
                            <x-button variant="secondary" class="w-full sm:w-auto" wire:click="saveIdp" loading="saveIdp">{{ __('idp.save') }}</x-button>
                            <x-confirm-dialog :title="__('idp.editor.submit_title')" :message="__('idp.editor.submit_body')" :confirm="__('idp.editor.submit')" action="submit">
                                <x-slot:trigger><x-button class="w-full sm:w-auto">{{ __('idp.editor.submit') }}</x-button></x-slot:trigger>
                            </x-confirm-dialog>
                        </div>
                    </x-card>
                </section>
            @elseif ($idp->status === \App\Modules\Idp\Enums\IdpStatus::Submitted)
                <p class="rounded-lg border border-info bg-surface p-4 text-sm text-info" role="status">{{ __('idp.editor.waiting', ['name' => $idp->supervisor?->name ?? '—']) }}</p>
            @elseif ($idp->status === \App\Modules\Idp\Enums\IdpStatus::Approved)
                <x-card class="space-y-2">
                    @if ($idp->supervisor_note)
                        <p class="text-sm"><span class="text-muted">{{ __('idp.editor.returned_note') }}:</span> {{ $idp->supervisor_note }}</p>
                    @endif
                    <x-confirm-dialog :title="__('idp.editor.publish_title')" :message="__('idp.editor.publish_body')" :confirm="__('idp.editor.publish')" action="publish">
                        <x-slot:trigger><x-button class="w-full sm:w-auto">{{ __('idp.editor.publish') }}</x-button></x-slot:trigger>
                    </x-confirm-dialog>
                </x-card>
            @endif

            {{-- Rencana Pembelajaran + teks "Kompeten" (IDP-05) --}}
            @if (in_array($idp->status, [\App\Modules\Idp\Enums\IdpStatus::Active, \App\Modules\Idp\Enums\IdpStatus::Closed], true))
                <x-card class="space-y-3">
                    @if ($idp->learningPlan)
                        <x-button variant="secondary" :href="route('idp.plans.download', $idp)">
                            <x-heroicon-o-arrow-down-tray class="size-4" aria-hidden="true" />{{ __('idp.editor.download') }}
                        </x-button>
                    @else
                        <p class="flex items-center gap-2 text-sm text-info" role="status"><x-heroicon-o-clock class="size-4" aria-hidden="true" />{{ __('idp.editor.plan_processing') }}</p>
                    @endif
                    <div x-data="{ text: @js($this->expectation) }" class="space-y-2">
                        <label for="expectation" class="block text-sm font-medium text-ink">{{ __('idp.expectation.title') }}</label>
                        <textarea id="expectation" x-model="text" rows="4" class="block w-full rounded-md border border-line bg-surface px-3 py-2 text-sm text-ink"></textarea>
                        <p class="text-xs text-muted">{{ __('idp.expectation.hint') }}</p>
                        <x-button variant="secondary" size="sm"
                                  x-on:click="navigator.clipboard.writeText(text).then(() => $dispatch('toast', { type: 'success', message: @js(__('idp.expectation.copied')) }))">
                            <x-heroicon-o-clipboard-document class="size-4" aria-hidden="true" />{{ __('idp.expectation.copy') }}
                        </x-button>
                    </div>
                </x-card>
            @endif

            {{-- Check-in (IDP-06) & penutupan (IDP-07) --}}
            @if (in_array($idp->status, [\App\Modules\Idp\Enums\IdpStatus::Active, \App\Modules\Idp\Enums\IdpStatus::Closed], true))
                <section class="space-y-3">
                    <h2 class="text-base font-semibold text-navy">{{ __('idp.editor.checkins') }}</h2>
                    <p class="text-sm text-muted">{{ __('idp.editor.checkin_hint') }}</p>
                    @forelse ($idp->checkins as $checkin)
                        <x-card wire:key="ci-{{ $checkin->id }}" class="space-y-2">
                            <p class="text-sm font-medium">{{ __('idp.editor.checkin_quarter', ['quarter' => $checkin->quarter]) }} · <span class="text-muted">{{ $checkin->checkin_on->translatedFormat('j M Y') }}</span></p>
                            @foreach ($idp->items as $item)
                                <x-progress :value="$checkin->progress[$item->id] ?? 0" :label="$item->competency->name" />
                            @endforeach
                            <p class="text-sm">{{ $checkin->employee_note }}</p>
                            @if ($checkin->supervisor_note)
                                <p class="text-sm"><span class="text-muted">{{ __('idp.attributes.supervisor_note') }}:</span> {{ $checkin->supervisor_note }}</p>
                            @endif
                        </x-card>
                    @empty
                        <x-empty-state icon="calendar-days" :title="__('idp.editor.no_checkins')" />
                    @endforelse

                    @if ($idp->status === \App\Modules\Idp\Enums\IdpStatus::Active)
                        <x-card class="space-y-3">
                            @foreach ($idp->items as $item)
                                <x-input :label="__('idp.editor.progress_item', ['name' => $item->competency->name])" name="progress.{{ $item->id }}" type="number" min="0" max="100" wire:model="progress.{{ $item->id }}" />
                            @endforeach
                            <x-textarea :label="__('idp.attributes.employee_note')" name="employee_note" wire:model="checkinNote" maxlength="2000" />
                            <x-button wire:click="saveCheckin" loading="saveCheckin">{{ __('idp.editor.checkin_save') }}</x-button>
                        </x-card>
                    @endif
                </section>

                <section class="space-y-3">
                    <h2 class="text-base font-semibold text-navy">{{ __('idp.editor.closing') }}</h2>
                    @if ($idp->status === \App\Modules\Idp\Enums\IdpStatus::Closed)
                        <x-card class="space-y-2 text-sm">
                            <p class="font-medium text-success">{{ __('idp.editor.closed', ['date' => $idp->closed_at?->translatedFormat('j M Y')]) }}</p>
                            <p><span class="text-muted">{{ __('idp.attributes.closing_reflection') }}:</span> {{ $idp->closing_reflection }}</p>
                            <p><span class="text-muted">{{ __('idp.attributes.closing_assessment') }}:</span> {{ $idp->closing_assessment }}</p>
                        </x-card>

                        {{-- Laporan Hasil Pembelajaran (JPR-05) --}}
                        <x-card class="space-y-2">
                            <p class="text-sm text-muted">{{ __('jp.report.hint') }}</p>
                            @if ($idp->learningReport)
                                <x-button variant="secondary" :href="route('jp.reports.download', $idp)">
                                    <x-heroicon-o-arrow-down-tray class="size-4" aria-hidden="true" />{{ __('jp.report.download') }}
                                </x-button>
                            @else
                                @error('report') <x-error-state :message="$message" /> @enderror
                                <x-confirm-dialog :title="__('jp.report.issue_title')" :message="__('jp.report.issue_body')" :confirm="__('jp.report.issue')" action="issueReport">
                                    <x-slot:trigger><x-button class="w-full sm:w-auto">{{ __('jp.report.issue') }}</x-button></x-slot:trigger>
                                </x-confirm-dialog>
                            @endif
                        </x-card>
                    @else
                        <x-card class="space-y-3">
                            <x-textarea :label="__('idp.attributes.closing_reflection')" name="closing_reflection" wire:model="reflection" rows="4" maxlength="5000" />
                            <x-button variant="secondary" wire:click="saveReflection" loading="saveReflection">{{ __('idp.editor.reflection_save') }}</x-button>
                        </x-card>
                    @endif
                </section>
            @endif
        </div>
    @endif
</div>
