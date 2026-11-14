@props(['title' => null])
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ? __($title).' · ' : '' }}{{ config('vpos.name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=JetBrains+Mono:wght@400;500;600&family=Kantumruy+Pro:wght@400;500;600&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen" x-data="{ sidebarOpen: false }">
    <x-layout.sidebar />
    <div x-show="sidebarOpen" x-cloak @click="sidebarOpen = false" class="fixed inset-0 z-30 bg-neutral-950/30 lg:hidden"></div>

    <div class="lg:pl-64">
        <x-layout.topbar />
        <main class="mx-auto max-w-8xl px-4 py-6 sm:px-8">
            <x-ui.flash />
            {{ $slot }}
        </main>
    </div>
</body>
</html>
