<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ isset($title) ? $title.' — ' : '' }}{{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen bg-canvas font-sans text-sm text-ink antialiased">
@php($menu = app(\App\Modules\Fondasi\Services\NavigationMenu::class))
<a href="#konten" class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-50 focus:rounded-md focus:bg-surface focus:p-3">{{ __('ui.skip_to_content') }}</a>
<div class="lg:flex">
    <aside class="hidden min-h-screen w-64 shrink-0 bg-navy text-surface lg:block">
        <div class="px-6 py-5 text-base font-bold">{{ config('app.name') }}</div>
        <nav aria-label="{{ __('ui.main_nav') }}">
            <ul class="space-y-1 px-3">
                @foreach ($menu->for(auth()->user()) as $item)
                    <li>
                        <a href="{{ route($item['route']) }}" @class(['flex min-h-11 items-center gap-3 rounded-md px-3 font-medium', 'bg-navy-700' => request()->routeIs($item['active']), 'hover:bg-navy-700' => ! request()->routeIs($item['active'])]) @if (request()->routeIs($item['active'])) aria-current="page" @endif>
                            <x-dynamic-component :component="'heroicon-o-'.$item['icon']" class="size-6" aria-hidden="true" />
                            {{ $item['label'] }}
                        </a>
                    </li>
                @endforeach
            </ul>
        </nav>
    </aside>

    <div class="min-w-0 flex-1">
        <header class="flex items-center justify-between bg-navy px-4 py-3 text-surface lg:bg-surface lg:text-ink lg:shadow-card">
            <span class="text-base font-bold lg:hidden">{{ config('app.name') }}</span>
            <span class="hidden text-sm lg:block">{{ auth()->user()->displayName() }}</span>
            <div class="flex items-center gap-1">
            @php($unread = auth()->user()->unreadNotifications()->count())
            <a href="{{ route('fondasi.notifications.index') }}" class="relative inline-flex min-h-11 min-w-11 items-center justify-center rounded-md"
               aria-label="{{ $unread > 0 ? __('fondasi.notifications.unread_count', ['count' => $unread]) : __('fondasi.notifications.title') }}">
                <x-heroicon-o-bell class="size-6" aria-hidden="true" />
                @if ($unread > 0)
                    <span class="absolute right-1 top-1 rounded-md bg-orange px-1 text-xs font-bold text-ink" aria-hidden="true">{{ $unread > 9 ? '9+' : $unread }}</span>
                @endif
            </a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="inline-flex min-h-11 items-center gap-2 rounded-md px-3 text-sm font-medium">
                    <x-heroicon-o-arrow-right-start-on-rectangle class="size-5" aria-hidden="true" />
                    <span>{{ __('fondasi.auth.logout') }}</span>
                </button>
            </form>
            </div>
        </header>

        <main id="konten" class="mx-auto max-w-5xl px-4 pb-24 pt-4 lg:px-6 lg:pb-8 lg:pt-6">
            {{ $slot }}
        </main>
    </div>
</div>

<nav class="fixed inset-x-0 bottom-0 z-40 border-t border-line bg-surface lg:hidden" aria-label="{{ __('ui.main_nav') }}">
    <ul class="grid grid-cols-4">
        @foreach ($menu->mobile(auth()->user()) as $item)
            <li>
                <a href="{{ route($item['route']) }}" @class(['flex min-h-14 flex-col items-center justify-center gap-0.5 text-xs font-medium', 'text-teal-700' => request()->routeIs($item['active']), 'text-muted' => ! request()->routeIs($item['active'])]) @if (request()->routeIs($item['active'])) aria-current="page" @endif>
                    <x-dynamic-component :component="'heroicon-o-'.$item['icon']" class="size-6" aria-hidden="true" />
                    {{ $item['label'] }}
                </a>
            </li>
        @endforeach
    </ul>
</nav>

<x-toast />
@livewireScripts
</body>
</html>
