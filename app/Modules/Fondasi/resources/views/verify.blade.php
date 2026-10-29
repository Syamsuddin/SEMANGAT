<x-layouts.guest-page :title="__('fondasi.verify.title')">
    <x-card :title="__('fondasi.verify.title')">
        @if ($document->isRevoked())
            <p class="mb-4 flex items-center gap-2 rounded-md border border-danger p-3 text-sm font-semibold text-danger" role="status">
                <x-heroicon-o-x-circle class="size-5" aria-hidden="true" /> {{ __('fondasi.verify.revoked', ['date' => $document->revoked_at->translatedFormat('j M Y')]) }}
            </p>
        @else
            <p class="mb-4 flex items-center gap-2 rounded-md border border-success p-3 text-sm font-semibold text-success" role="status">
                <x-heroicon-o-check-badge class="size-5" aria-hidden="true" /> {{ __('fondasi.verify.valid') }}
            </p>
        @endif
        <dl class="grid gap-3">
            <div><dt class="text-xs text-muted">{{ __('fondasi.verify.type') }}</dt><dd>{{ $document->type->label() }}</dd></div>
            <div><dt class="text-xs text-muted">{{ __('fondasi.verify.owner') }}</dt><dd>{{ $document->employee?->name ?? '—' }}</dd></div>
            <div><dt class="text-xs text-muted">{{ __('fondasi.verify.issued_at') }}</dt><dd>{{ $document->issued_at->translatedFormat('j M Y') }}</dd></div>
            <div><dt class="text-xs text-muted">{{ __('fondasi.verify.hash') }}</dt><dd class="break-all font-mono text-xs">{{ $document->sha256 }}</dd></div>
        </dl>
        <p class="mt-4 text-xs text-muted">{{ __('fondasi.verify.note') }}</p>
    </x-card>
</x-layouts.guest-page>
