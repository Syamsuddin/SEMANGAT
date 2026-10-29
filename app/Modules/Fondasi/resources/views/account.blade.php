<x-layouts.app-page :title="__('fondasi.account.title')">
    <div class="space-y-4">
        <h1 class="text-xl font-semibold text-navy">{{ __('fondasi.account.title') }}</h1>

        <x-card :title="__('fondasi.account.identity')">
            <dl class="grid gap-2">
                <div><dt class="text-xs text-muted">{{ __('fondasi.account.username') }}</dt><dd>{{ $user->username }}</dd></div>
                <div><dt class="text-xs text-muted">{{ __('fondasi.account.roles') }}</dt>
                    <dd class="mt-1 flex flex-wrap gap-1">
                        @forelse ($user->roles as $role)
                            <x-badge>{{ \App\Modules\Fondasi\Enums\Role::from($role->name)->label() }}</x-badge>
                        @empty
                            <span class="text-muted">—</span>
                        @endforelse
                    </dd>
                </div>
            </dl>
        </x-card>

        <x-card :title="__('fondasi.two_factor.title')">
            @if ($user->requiresTwoFactor() && ! $user->hasConfirmedTwoFactor())
                <p class="mb-3 flex items-start gap-2 rounded-md border border-orange bg-orange-50 p-3 text-sm text-ink" role="alert">
                    <x-heroicon-o-shield-exclamation class="size-5 shrink-0 text-warning" aria-hidden="true" />
                    {{ __('fondasi.two_factor.required') }}
                </p>
            @endif

            @if ($user->hasConfirmedTwoFactor())
                <p class="flex items-center gap-2 text-sm text-success">
                    <x-heroicon-o-shield-check class="size-5" aria-hidden="true" /> {{ __('fondasi.two_factor.enabled') }}
                </p>
            @elseif ($pendingConfirmation)
                <p class="text-sm">{{ __('fondasi.two_factor.scan') }}</p>
                <img class="my-4 size-48 rounded-md border border-line bg-surface p-3" alt="{{ __('fondasi.two_factor.qr_alt') }}"
                     src="data:image/svg+xml;base64,{{ base64_encode($user->twoFactorQrCodeSvg()) }}">
                <form method="POST" action="{{ route('two-factor.confirm') }}" class="space-y-3">
                    @csrf
                    <x-input name="code" :label="__('fondasi.two_factor.code')" inputmode="numeric" autocomplete="one-time-code" required />
                    @error('code', 'confirmTwoFactorAuthentication')
                        <p class="text-xs text-danger">{{ $message }}</p>
                    @enderror
                    <x-button type="submit" class="w-full sm:w-auto">{{ __('fondasi.two_factor.confirm') }}</x-button>
                </form>
            @else
                <p class="mb-3 text-sm">{{ __('fondasi.two_factor.intro') }}</p>
                <form method="POST" action="{{ route('two-factor.enable') }}">
                    @csrf
                    <x-button type="submit" class="w-full sm:w-auto">{{ __('fondasi.two_factor.enable') }}</x-button>
                </form>
            @endif
        </x-card>
    </div>
</x-layouts.app-page>
