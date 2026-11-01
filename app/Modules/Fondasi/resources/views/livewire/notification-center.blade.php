<div class="space-y-4">
    <div class="flex flex-wrap items-center justify-between gap-2">
        <h1 class="text-xl font-semibold text-navy">{{ __('fondasi.notifications.title') }}</h1>
        @if ($this->unreadCount > 0)
            <x-button variant="ghost" size="sm" wire:click="markAllRead" loading="markAllRead">{{ __('fondasi.notifications.mark_all') }}</x-button>
        @endif
    </div>

    <div wire:loading.delay wire:target="open,markAllRead,loadMore"><x-skeleton :lines="3" /></div>
    <div wire:loading.remove wire:target="open,markAllRead,loadMore">
        @if ($this->notifications->isEmpty())
            <x-empty-state icon="bell" :title="__('fondasi.notifications.empty')" />
        @else
            <ul class="space-y-2">
                @foreach ($this->notifications as $notification)
                    <li wire:key="n-{{ $notification->id }}">
                        <button type="button" wire:click="open('{{ $notification->id }}')"
                                @class(['flex min-h-11 w-full items-start gap-3 rounded-lg border bg-surface p-4 text-left shadow-card', 'border-teal' => $notification->read_at === null, 'border-line' => $notification->read_at !== null])>
                            @if ($notification->read_at === null)
                                <x-heroicon-s-bell-alert class="mt-0.5 size-5 shrink-0 text-teal-700" aria-hidden="true" />
                            @else
                                <x-heroicon-o-bell class="mt-0.5 size-5 shrink-0 text-muted" aria-hidden="true" />
                            @endif
                            <span class="min-w-0">
                                <span @class(['block text-sm', 'font-semibold text-ink' => $notification->read_at === null, 'text-ink' => $notification->read_at !== null])>{{ $notification->data['message'] ?? '' }}</span>
                                @if (! empty($notification->data['note']))
                                    <span class="block text-sm text-muted">{{ $notification->data['note'] }}</span>
                                @endif
                                <span class="block text-xs text-muted">
                                    {{ $notification->created_at?->timezone('Asia/Makassar')->translatedFormat('j M Y, H.i') }} WITA
                                    @if ($notification->read_at === null) · <span class="font-medium text-teal-700">{{ __('fondasi.notifications.unread') }}</span> @endif
                                </span>
                            </span>
                        </button>
                    </li>
                @endforeach
            </ul>
            @if ($this->hasMore)
                <div class="mt-3 flex justify-center">
                    <x-button variant="secondary" wire:click="loadMore" loading="loadMore" class="w-full sm:w-auto">{{ __('fondasi.notifications.load_more') }}</x-button>
                </div>
            @endif
        @endif
    </div>
</div>
