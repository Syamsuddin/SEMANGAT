<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ isset($title) ? $title.' — ' : '' }}{{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-canvas font-sans text-sm text-ink antialiased">
<header class="bg-navy px-4 py-4 text-surface">
    <p class="mx-auto max-w-md text-base font-bold">{{ config('app.name') }}</p>
</header>
<main class="mx-auto max-w-md px-4 py-6">
    {{ $slot }}
</main>
</body>
</html>
