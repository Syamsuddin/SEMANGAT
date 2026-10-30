<div class="space-y-4">
    <h1 class="text-xl font-semibold text-navy">{{ __('kompetensi.self.title') }}</h1>
    <p class="text-sm text-muted">{{ __('kompetensi.self.intro') }}</p>

    @if ($this->items->isEmpty())
        <x-empty-state icon="clipboard-document-list" :title="__('kompetensi.profile.no_skj_title')">
            <p class="text-xs text-muted">{{ __('kompetensi.profile.no_skj_body') }}</p>
        </x-empty-state>
    @else
        @if ($this->pending->isNotEmpty())
            <p class="rounded-md border border-dashed border-orange bg-orange-50 p-3 text-sm text-ink">{{ __('kompetensi.self.pending_note') }}</p>
        @endif
        @error('levels') <x-error-state :message="$message" /> @enderror

        <form wire:submit="submit" class="space-y-3">
            @foreach ($this->items as $item)
                <x-card wire:key="item-{{ $item->competency_id }}">
                    <p class="font-medium text-ink">{{ $item->competency->name }}</p>
                    <p class="mb-3 text-xs text-muted">{{ __('kompetensi.profile.required') }}: {{ __('kompetensi.profile.level', ['level' => $item->required_level]) }}</p>
                    @php($descriptors = $item->competency->level_descriptors ?? [])
                    <x-select :label="__('kompetensi.self.level_label')" name="levels.{{ $item->competency_id }}" wire:model="levels.{{ $item->competency_id }}"
                              :placeholder="__('kompetensi.self.skip')"
                              :options="collect(range(1, 5))->mapWithKeys(fn ($l) => [$l => __('kompetensi.profile.level', ['level' => $l]).(isset($descriptors[$l]) ? ' — '.$descriptors[$l] : '')])->all()" />
                </x-card>
            @endforeach
            <div class="flex justify-end">
                <x-button type="submit" class="w-full sm:w-auto" loading="submit">{{ __('kompetensi.self.submit') }}</x-button>
            </div>
        </form>
    @endif
</div>
