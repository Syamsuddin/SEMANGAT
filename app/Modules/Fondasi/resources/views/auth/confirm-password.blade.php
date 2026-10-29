<x-layouts.guest-page :title="__('fondasi.auth.confirm_title')">
    <x-card :title="__('fondasi.auth.confirm_title')">
        <p class="mb-4 text-sm">{{ __('fondasi.auth.confirm_intro') }}</p>
        <form method="POST" action="{{ route('password.confirm.store') }}" class="space-y-4">
            @csrf
            <x-input name="password" type="password" :label="__('fondasi.auth.password')" autocomplete="current-password" required autofocus />
            <x-button type="submit" class="w-full">{{ __('fondasi.auth.confirm') }}</x-button>
        </form>
    </x-card>
</x-layouts.guest-page>
