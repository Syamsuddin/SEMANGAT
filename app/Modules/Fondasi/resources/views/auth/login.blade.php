<x-layouts.guest-page :title="__('fondasi.auth.login_title')">
    <x-card :title="__('fondasi.auth.login_title')">
        <form method="POST" action="{{ route('login') }}" class="space-y-4">
            @csrf
            <x-input name="username" :label="__('fondasi.auth.nip')" :value="old('username')" inputmode="numeric" autocomplete="username" maxlength="32" required autofocus />
            <x-input name="password" type="password" :label="__('fondasi.auth.password')" autocomplete="current-password" required />
            <label class="flex min-h-11 items-center gap-2 text-sm">
                <input type="checkbox" name="remember" class="size-5 rounded-md border-line"> {{ __('fondasi.auth.remember') }}
            </label>
            <x-button type="submit" class="w-full">{{ __('fondasi.auth.login') }}</x-button>
        </form>
    </x-card>
</x-layouts.guest-page>
