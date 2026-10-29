<x-layouts.guest-page :title="__('fondasi.two_factor.challenge_title')">
    <x-card :title="__('fondasi.two_factor.challenge_title')">
        <form method="POST" action="{{ route('two-factor.login.store') }}" class="space-y-4" x-data="{ recovery: false }">
            @csrf
            <div x-show="! recovery">
                <x-input name="code" :label="__('fondasi.two_factor.code')" inputmode="numeric" autocomplete="one-time-code" autofocus />
            </div>
            <div x-show="recovery" x-cloak>
                <x-input name="recovery_code" :label="__('fondasi.two_factor.recovery_code')" autocomplete="off" />
            </div>
            <x-button type="submit" class="w-full">{{ __('fondasi.auth.login') }}</x-button>
            <x-button variant="ghost" class="w-full" x-on:click="recovery = ! recovery">{{ __('fondasi.two_factor.toggle_recovery') }}</x-button>
        </form>
    </x-card>
</x-layouts.guest-page>
