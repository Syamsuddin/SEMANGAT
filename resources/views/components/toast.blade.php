{{-- x-toast: dengarkan event Livewire `toast` + flash sesi; hilang 5 dtk; aria-live (docs/26). --}}
@php($flash = session('toast'))
<div x-data="{
        items: [],
        push(t) { const id = Date.now() + Math.random(); this.items.push({ id, ...t }); setTimeout(() => this.remove(id), 5000) },
        remove(id) { this.items = this.items.filter(i => i.id !== id) },
     }"
     x-init="@if ($flash) push(@js($flash)) @endif"
     x-on:toast.window="push($event.detail)"
     class="pointer-events-none fixed inset-x-0 bottom-20 z-50 flex flex-col items-center gap-2 px-4 lg:bottom-6 lg:items-end"
     aria-live="polite">
    <template x-for="item in items" :key="item.id">
        <div class="pointer-events-auto flex w-full max-w-sm items-start gap-3 rounded-lg border bg-surface p-3 shadow-card"
             :class="{ 'border-success': item.type === 'success', 'border-orange': item.type === 'warning', 'border-danger': item.type === 'danger' }"
             role="status">
            <p class="flex-1 text-sm text-ink" x-text="item.message"></p>
            <button type="button" class="-m-2 inline-flex size-11 items-center justify-center text-muted" x-on:click="remove(item.id)" aria-label="{{ __('ui.close') }}">
                <x-heroicon-o-x-mark class="size-5" aria-hidden="true" />
            </button>
        </div>
    </template>
</div>
