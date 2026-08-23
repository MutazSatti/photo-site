<!DOCTYPE html>
<html lang="ar" dir="rtl" class="scroll-pt-24">
<head>
    {{-- يضبط الوضع الداكن قبل رسم الصفحة لتفادي ومضة بيضاء --}}
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

    @include('partials.head')
</head>

<body class="min-h-screen antialiased bg-white font-sans text-ink-800 dark:bg-ink-950 dark:text-ink-200">

    {{-- شريط تقدّم رفيع يظهر أثناء التنقّل بـ wire:navigate --}}
    <div
        x-data="{ loading: false }"
        x-on:livewire:navigate.window="loading = true"
        x-on:livewire:navigated.window="loading = false"
        class="fixed inset-x-0 top-0 z-100 h-0.5 pointer-events-none"
        aria-hidden="true"
    >
        <div
            class="h-full transition-all duration-500 nav-progress ease-smooth"
            x-bind:style="loading ? 'width:85%' : 'width:0;opacity:0'"
        ></div>
    </div>

    <a
        href="#main"
        class="sr-only focus:not-sr-only focus:fixed focus:top-4 focus:start-4 focus:z-100 focus:rounded-lg focus:bg-brand-500 focus:px-4 focus:py-2 focus:font-bold focus:text-white"
    >
        تخطَّ إلى المحتوى
    </a>

    <x-site.header />

    <main id="main" class="min-h-[60vh]">
        {{ $slot }}
    </main>

    <x-site.footer />

    {{-- زر واتساب عائم — أقصر طريق للحجز على الجوال --}}
    <a
        href="{{ whatsapp_url() }}"
        target="_blank"
        rel="noopener"
        class="fixed z-50 flex items-center justify-center text-white transition-transform rounded-full shadow-lg bottom-5 end-5 size-14 bg-[#25D366] shadow-black/20 hover:scale-105 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-500"
        aria-label="تواصل عبر الواتساب"
    >
        <x-icon name="whatsapp" :size="26" />
    </a>

    @livewireScripts
</body>
</html>
