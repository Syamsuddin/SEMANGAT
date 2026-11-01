<div class="space-y-4">
    <div class="flex flex-wrap items-center justify-between gap-2">
        <h1 class="text-xl font-semibold text-navy">{{ __('idp.team.title') }}</h1>
        @can(\App\Modules\Fondasi\Enums\Permission::IdpViewUnlinked->value)
            <a href="{{ route('idp.unlinked.index') }}" class="inline-flex min-h-11 items-center gap-1 text-sm font-medium text-teal-700 underline-offset-2 hover:underline">
                <x-heroicon-o-link-slash class="size-4" aria-hidden="true" />{{ __('idp.nav.unlinked') }}
            </a>
        @endcan
    </div>
    <p class="text-sm text-muted">{{ __('idp.team.intro') }}</p>

    <div wire:loading.delay wire:target="approveSelected,returnIdp,close"><x-skeleton :lines="3" /></div>
    @error('selected') <x-error-state :message="$message" /> @enderror

    <section class="space-y-3" wire:loading.remove wire:target="approveSelected,returnIdp,close">
        <h2 class="text-base font-semibold text-navy">{{ __('idp.team.pending') }}</h2>

        @if ($this->pending->isEmpty())
            <x-empty-state icon="check-circle" :title="__('idp.team.empty_title')">
                <p class="text-xs text-muted">{{ __('idp.team.empty_body') }}</p>
            </x-empty-state>
        @else
            <x-card class="space-y-3">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <label class="inline-flex min-h-11 items-center gap-2 text-sm">
                        <input type="checkbox" class="size-5 rounded border-line" wire:click="toggleAll" @checked(count($selected) === $this->pending->count())>
                        {{ __('idp.team.select_all') }}
                    </label>
                    <span class="text-xs text-muted" aria-live="polite">{{ __('idp.team.selected', ['count' => count($selected)]) }}</span>
                </div>
                <x-textarea :label="__('idp.team.note')" name="note" wire:model="note" rows="2" maxlength="2000" />
                <x-confirm-dialog :title="__('idp.team.approve_title')" :message="__('idp.team.approve_body')" :confirm="__('idp.team.approve')" action="approveSelected">
                    <x-slot:trigger><x-button class="w-full sm:w-auto" :disabled="$selected === []">{{ __('idp.team.approve') }}</x-button></x-slot:trigger>
                </x-confirm-dialog>
            </x-card>

            <ul class="space-y-3">
                @foreach ($this->pending as $idp)
                    <li wire:key="p-{{ $idp->id }}" class="rounded-lg border border-line bg-surface p-4 shadow-card">
                        <label class="flex min-h-11 items-start gap-3">
                            <input type="checkbox" class="mt-1 size-5 rounded border-line" value="{{ $idp->id }}" wire:model.live="selected">
                            <span>
                                <span class="block font-medium text-ink">{{ $idp->employee->name }}</span>
                                <span class="block text-xs text-muted">{{ $idp->employee->position?->name ?? '—' }} · {{ __('idp.team.items', ['count' => $idp->items->count()]) }} · {{ $idp->target_jp }} JP</span>
                            </span>
                        </label>
                        <ul class="mt-2 space-y-1 text-sm">
                            @foreach ($idp->items as $item)
                                <li class="flex flex-wrap items-center gap-1">
                                    {{ $item->competency->name }}
                                    <span class="text-xs text-muted">{{ __('idp.editor.level_range', ['start' => $item->start_level, 'target' => $item->target_level]) }} · {{ __('idp.team.activities', ['count' => $item->activities->count()]) }}</span>
                                    @if ($item->workResult)
                                        <x-badge>{{ $item->workResult->code }}</x-badge>
                                    @else
                                        <x-badge variant="warning"><x-heroicon-s-link-slash class="size-4" aria-hidden="true" />{{ __('idp.unlinked') }}</x-badge>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                        @if ($idp->career_aspiration)
                            <p class="mt-2 text-xs text-muted">{{ __('idp.attributes.career_aspiration') }}: {{ $idp->career_aspiration }}</p>
                        @endif
                        <details class="mt-3">
                            <summary class="min-h-11 cursor-pointer text-sm font-medium text-teal-700">{{ __('idp.team.return') }}</summary>
                            <div class="mt-2 space-y-2">
                                <x-input :label="__('idp.team.return_note')" name="note" id="return-{{ $idp->id }}" wire:model="returnNotes.{{ $idp->id }}" maxlength="2000" />
                                <x-confirm-dialog :title="__('idp.team.return_title')" :message="__('idp.team.return_body')" :confirm="__('idp.team.return')" action="returnIdp({{ $idp->id }})" variant="danger">
                                    <x-slot:trigger><x-button variant="secondary" class="w-full sm:w-auto">{{ __('idp.team.return') }}</x-button></x-slot:trigger>
                                </x-confirm-dialog>
                            </div>
                        </details>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>

    <section class="space-y-3" wire:loading.remove wire:target="approveSelected,returnIdp,close">
        <h2 class="text-base font-semibold text-navy">{{ __('idp.team.active') }}</h2>
        @forelse ($this->active as $idp)
            <x-card wire:key="a-{{ $idp->id }}" class="space-y-3">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <p class="font-medium text-ink">{{ $idp->employee->name }}</p>
                    @if ($idp->learningPlan)
                        <a href="{{ route('idp.plans.download', $idp) }}" class="inline-flex min-h-11 items-center gap-1 text-sm text-teal-700 underline-offset-2 hover:underline">
                            <x-heroicon-o-arrow-down-tray class="size-4" aria-hidden="true" />{{ __('idp.editor.download') }}
                        </a>
                    @endif
                </div>

                @foreach ($idp->checkins as $checkin)
                    <div wire:key="ci-{{ $checkin->id }}" class="space-y-2 border-t border-line pt-2">
                        <p class="text-sm font-medium">{{ __('idp.editor.checkin_quarter', ['quarter' => $checkin->quarter]) }} · <span class="text-muted">{{ $checkin->checkin_on->translatedFormat('j M Y') }}</span></p>
                        @foreach ($idp->items as $item)
                            <x-progress :value="$checkin->progress[$item->id] ?? 0" :label="$item->competency->name" />
                        @endforeach
                        <p class="text-sm">{{ $checkin->employee_note }}</p>
                        @if ($checkin->supervisor_note)
                            <p class="text-sm"><span class="text-muted">{{ __('idp.attributes.supervisor_note') }}:</span> {{ $checkin->supervisor_note }}</p>
                        @else
                            <x-textarea :label="__('idp.team.checkin_note')" name="supervisor_note" id="cn-{{ $checkin->id }}" wire:model="checkinNotes.{{ $checkin->id }}" rows="2" maxlength="2000" />
                            <x-button variant="secondary" size="sm" wire:click="reviewCheckin({{ $checkin->id }})" loading="reviewCheckin({{ $checkin->id }})">{{ __('idp.team.checkin_review') }}</x-button>
                        @endif
                    </div>
                @endforeach

                <div class="space-y-2 border-t border-line pt-2">
                    @if (blank($idp->closing_reflection))
                        <p class="text-xs text-muted">{{ __('idp.team.awaiting_reflection') }}</p>
                    @else
                        <p class="text-sm"><span class="text-muted">{{ __('idp.attributes.closing_reflection') }}:</span> {{ $idp->closing_reflection }}</p>
                        <x-textarea :label="__('idp.team.close_assessment')" name="closing_assessment" id="ca-{{ $idp->id }}" wire:model="assessments.{{ $idp->id }}" rows="3" maxlength="5000" />
                        <x-confirm-dialog :title="__('idp.team.close_title')" :message="__('idp.team.close_body')" :confirm="__('idp.team.close')" action="close({{ $idp->id }})">
                            <x-slot:trigger><x-button class="w-full sm:w-auto">{{ __('idp.team.close') }}</x-button></x-slot:trigger>
                        </x-confirm-dialog>
                    @endif
                </div>
            </x-card>
        @empty
            <x-empty-state icon="calendar-days" :title="__('idp.team.active_empty')" />
        @endforelse
    </section>
</div>
