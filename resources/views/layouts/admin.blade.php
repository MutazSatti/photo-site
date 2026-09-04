@php
    use App\Models\ContactMessage;

    $unread = once(fn () => ContactMessage::unread()->count());

    $nav = [
        ['route' => 'admin.dashboard', 'label' => 'لوحة المعلومات', 'icon' => 'dashboard'],
        ['route' => 'admin.posts', 'label' => 'الأعمال والمحتوى', 'icon' => 'images'],
        ['route' => 'admin.sections', 'label' => 'الأقسام', 'icon' => 'layers'],
        ['route' => 'admin.faqs', 'label' => 'الأسئلة الشائعة', 'icon' => 'help'],
        ['route' => 'admin.testimonials', 'label' => 'آراء العملاء', 'icon' => 'star'],
        ['route' => 'admin.clients', 'label' => 'الجهات والعملاء', 'icon' => 'building'],
        ['route' => 'admin.google', 'label' => 'تقييمات Google', 'icon' => 'google'],
        ['route' => 'admin.messages', 'label' => 'الرسائل', 'icon' => 'inbox', 'badge' => $unread],
        ['route' => 'admin.settings', 'label' => 'الإعدادات', 'icon' => 'settings'],
    ];
@endphp

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
    <title>{{ $title ?? 'لوحة التحكم' }} — {{ config('site.owner_name') }}</title>
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <link rel="preload" href="/fonts/almarai/almarai-400-arabic.woff2" as="font" type="font/woff2" crossorigin>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="min-h-screen antialiased bg-ink-50 font-sans text-ink-800 dark:bg-ink-950 dark:text-ink-200">

    <div x-data="{ sidebar: false }" x-on:livewire:navigated.window="sidebar = false">

        {{-- ================= الشريط العلوي ================= --}}
        <header class="sticky top-0 z-40 border-b bg-white/90 border-ink-200 backdrop-blur-lg dark:border-ink-800 dark:bg-ink-900/90">
            <div class="flex items-center gap-3 px-4 h-14 sm:px-6">
                <button
                    type="button"
                    x-on:click="sidebar = !sidebar"
                    class="inline-flex items-center justify-center rounded-lg size-9 text-ink-600 hover:bg-ink-100 lg:hidden dark:text-ink-400 dark:hover:bg-ink-800"
                    aria-label="القائمة"
                >
                    <x-icon name="menu" :size="20" />
                </button>

                <a href="{{ route('admin.dashboard') }}" wire:navigate class="flex items-center gap-2">
                    <span class="flex items-center justify-center rounded-lg size-8 bg-brand-500 text-ink-950">
                        <x-icon name="aperture" :size="17" :stroke="2" />
                    </span>
                    <span class="text-sm font-extrabold text-ink-900 dark:text-ink-50">لوحة التحكم</span>
                </a>

                <div class="flex items-center gap-1 ms-auto">
                    <a
                        href="{{ route('home') }}"
                        class="hidden items-center gap-1.5 rounded-lg px-3 py-2 text-xs font-bold text-ink-600 transition-colors hover:bg-ink-100 sm:inline-flex dark:text-ink-400 dark:hover:bg-ink-800"
                        target="_blank"
                        rel="noopener"
                    >
                        <x-icon name="external-link" :size="14" />
                        عرض الموقع
                    </a>

                    <x-site.theme-toggle />

                    <div x-data="{ menu: false }" class="relative">
                        <button
                            type="button"
                            x-on:click="menu = !menu"
                            class="flex items-center justify-center text-sm font-extrabold rounded-full size-9 bg-ink-900 text-white dark:bg-brand-500 dark:text-ink-950"
                            aria-label="حساب المستخدم"
                        >
                            {{ mb_substr(auth()->user()?->name ?? '؟', 0, 1) }}
                        </button>

                        <div
                            x-show="menu"
                            x-cloak
                            x-on:click.outside="menu = false"
                            x-transition
                            class="absolute z-50 p-2 mt-2 bg-white border shadow-xl end-0 w-56 rounded-2xl border-ink-200 shadow-black/5 dark:border-ink-800 dark:bg-ink-900"
                        >
                            <div class="px-3 py-2 border-b border-ink-200 dark:border-ink-800">
                                <p class="text-sm font-extrabold truncate text-ink-900 dark:text-ink-100">{{ auth()->user()?->name }}</p>
                                <p class="text-xs truncate text-ink-500 dark:text-ink-400" dir="ltr">{{ auth()->user()?->email }}</p>
                            </div>

                            <a href="{{ route('profile.edit') }}" wire:navigate class="flex items-center gap-2.5 rounded-lg px-3 py-2 text-sm text-ink-700 transition-colors hover:bg-ink-100 dark:text-ink-300 dark:hover:bg-ink-800">
                                <x-icon name="user" :size="15" />
                                الملف الشخصي
                            </a>

                            <a href="{{ route('security.edit') }}" wire:navigate class="flex items-center gap-2.5 rounded-lg px-3 py-2 text-sm text-ink-700 transition-colors hover:bg-ink-100 dark:text-ink-300 dark:hover:bg-ink-800">
                                <x-icon name="settings" :size="15" />
                                الأمان وكلمة المرور
                            </a>

                            <form method="POST" action="{{ route('logout') }}" class="pt-1 mt-1 border-t border-ink-200 dark:border-ink-800">
                                @csrf
                                <button type="submit" class="flex w-full items-center gap-2.5 rounded-lg px-3 py-2 text-sm text-red-600 transition-colors hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-950/40">
                                    <x-icon name="logout" :size="15" />
                                    تسجيل الخروج
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <div class="flex">
            {{-- ================= القائمة الجانبية ================= --}}
            <aside
                class="fixed inset-y-0 z-50 w-64 pt-14 transition-transform border-e bg-white start-0 border-ink-200 lg:sticky lg:top-14 lg:z-30 lg:h-[calc(100vh-3.5rem)] lg:translate-x-0 lg:pt-0 dark:border-ink-800 dark:bg-ink-900"
                x-bind:class="sidebar ? 'translate-x-0' : 'translate-x-full lg:translate-x-0'"
            >
                <nav class="p-3 space-y-1 overflow-y-auto h-full" aria-label="أقسام لوحة التحكم">
                    @foreach ($nav as $item)
                        @php $active = request()->routeIs($item['route'].'*'); @endphp

                        <a
                            href="{{ route($item['route']) }}"
                            wire:navigate
                            @if ($active) aria-current="page" @endif
                            class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-bold transition-colors {{ $active
                                ? 'bg-ink-900 text-white dark:bg-brand-500 dark:text-ink-950'
                                : 'text-ink-700 hover:bg-ink-100 dark:text-ink-300 dark:hover:bg-ink-800' }}"
                        >
                            <x-icon :name="$item['icon']" :size="17" />
                            {{ $item['label'] }}

                            @if (! empty($item['badge']))
                                <span class="flex items-center justify-center min-w-5 h-5 px-1.5 text-xs font-extrabold rounded-full ms-auto bg-red-500 text-white">
                                    {{ $item['badge'] }}
                                </span>
                            @endif
                        </a>
                    @endforeach
                </nav>
            </aside>

            {{-- طبقة تعتيم خلف القائمة على الجوال --}}
            <div
                x-show="sidebar"
                x-cloak
                x-on:click="sidebar = false"
                x-transition.opacity
                class="fixed inset-0 z-40 bg-ink-950/50 lg:hidden"
                aria-hidden="true"
            ></div>

            {{-- ================= المحتوى ================= --}}
            <main class="flex-1 min-w-0 p-4 sm:p-6 lg:p-8">
                <div class="mx-auto max-w-6xl">
                    {{ $slot }}
                </div>
            </main>
        </div>
    </div>

    {{-- إشعارات مختصرة تُطلق من مكوّنات Livewire عبر dispatch('notify', ...) --}}
    <div
        x-data="{
            items: [],
            add(detail) {
                const id = Date.now() + Math.random();
                this.items.push({ id, ...detail });
                setTimeout(() => this.items = this.items.filter(i => i.id !== id), 4000);
            },
        }"
        x-on:notify.window="add($event.detail)"
        class="fixed z-100 flex flex-col gap-2 bottom-5 start-5"
        role="status"
        aria-live="polite"
    >
        <template x-for="item in items" :key="item.id">
            <div
                x-transition:enter="transition ease-smooth duration-300"
                x-transition:enter-start="opacity-0 translate-y-2"
                x-transition:enter-end="opacity-100 translate-y-0"
                class="flex items-center gap-2.5 rounded-xl border border-ink-200 bg-white px-4 py-3 text-sm font-bold shadow-lg shadow-black/10 dark:border-ink-700 dark:bg-ink-800"
                x-bind:class="item.variant === 'danger' ? 'text-red-600 dark:text-red-400' : 'text-emerald-600 dark:text-emerald-400'"
            >
                <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <template x-if="item.variant === 'danger'">
                        <g><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3" /><path d="M12 9v4m0 4h.01" /></g>
                    </template>
                    <template x-if="item.variant !== 'danger'">
                        <g><path d="M21.801 10A10 10 0 1 1 17 3.335" /><path d="m9 11 3 3L22 4" /></g>
                    </template>
                </svg>
                <span x-text="item.message"></span>
            </div>
        </template>
    </div>

    @livewireScripts
</body>
</html>
