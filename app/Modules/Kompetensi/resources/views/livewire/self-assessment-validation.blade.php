<div class="space-y-4">
    <h1 class="text-xl font-semibold text-navy">{{ __('kompetensi.validation.title') }}</h1>
    <p class="text-sm text-muted">{{ __('kompetensi.validation.intro') }}</p>

    <div wire:loading.delay wire:target="approve,reject"><x-skeleton :lines="3" /></div>

    @if ($this->pending->isEmpty())
        <x-empty-state icon="check-circle" :title="__('kompetensi.validation.empty_title')">
            <p class="text-xs text-muted">{{ __('kompetensi.validation.empty_body') }}</p>
        </x-empty-state>
    @else
        <ul class="space-y-3" wire:loading.remove wire:target="approve,reject">
            @foreach ($this->pending as $a)
                <li wire:key="p-{{ $a->id }}" class="rounded-lg border border-line bg-surface p-4 shadow-card">
                    <p class="font-medium text-ink">{{ $a->employee->name }}</p>
                    <p class="text-xs text-muted">{{ $a->employee->position?->name ?? '—' }}</p>
                    <p class="mt-2 text-sm">{{ $a->competency->name }} — <span class="text-muted">{{ __('kompetensi.validation.claimed') }}:</span> {{ __('kompetensi.profile.level', ['level' => $a->level]) }}</p>
                    <div class="mt-3 space-y-2">
                        <x-input :label="__('kompetensi.validation.note')" name="note" id="note-{{ $a->id }}" wire:model="notes.{{ $a->id }}" maxlength="255" />
                        <div class="flex flex-col gap-2 sm:flex-row sm:justify-end">
                            <x-confirm-dialog :title="__('kompetensi.validation.reject_title')" :message="__('kompetensi.validation.reject_body')"
                                              :confirm="__('kompetensi.validation.reject')" action="reject({{ $a->id }})" variant="danger">
                                <x-slot:trigger><x-button variant="secondary" class="w-full sm:w-auto">{{ __('kompetensi.validation.reject') }}</x-button></x-slot:trigger>
                            </x-confirm-dialog>
                            <x-button class="w-full sm:w-auto" wire:click="approve({{ $a->id }})" loading="approve">{{ __('kompetensi.validation.approve') }}</x-button>
                        </div>
                    </div>
                </li>
            @endforeach
        </ul>
    @endif
</div>
