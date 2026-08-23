<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <script>
        (function () {
            try {
                var stored = localStorage.getItem('theme');
                var dark = stored ? stored === 'dark'
                    : window.matchMedia('(prefers-color-scheme: dark)').matches;
                document.documentElement.classList.toggle('dark', dark);
            } catch (e) {}
        })();
    </script>

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $title ?? 'الدخول' }} — {{ config('site.owner_name') }}</title>
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <link rel="preload" href="/fonts/almarai/almarai-400-arabic.woff2" as="font" type="font/woff2" crossorigin>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="min-h-screen antialiased bg-ink-50 font-sans text-ink-800 dark:bg-ink-950 dark:text-ink-200">
    <div class="flex flex-col items-center justify-center min-h-screen px-4 py-12">

        <a href="{{ route('home') }}" wire:navigate class="flex items-center gap-2.5">
            <span class="flex items-center justify-center rounded-xl size-10 bg-brand-500 text-ink-950">
                <x-icon name="aperture" :size="22" :stroke="2" />
            </span>
            <span class="text-lg font-extrabold text-ink-900 dark:text-ink-50">{{ config('site.owner_name') }}</span>
        </a>

        <div class="w-full max-w-md p-7 mt-8 bg-white border shadow-sm rounded-3xl border-ink-200 shadow-black/5 dark:border-ink-800 dark:bg-ink-900">
            {{ $slot }}
        </div>

        <a href="{{ route('home') }}" wire:navigate class="mt-8 inline-flex items-center gap-1.5 text-sm text-ink-500 transition-colors hover:text-brand-600 dark:hover:text-brand-400">
            <x-icon name="arrow-right" :size="15" />
            العودة إلى الموقع
        </a>
    </div>

    @livewireScripts
</body>
</html>
