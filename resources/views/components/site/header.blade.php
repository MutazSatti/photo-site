@php
    use App\Models\Section;

    // قائمة التنقّل مبنية من الأقسام الفعلية. once() يحفظ النتيجة داخل الطلب
    // الواحد فيستفيد منها الرأس والتذييل باستعلام واحد. لا نخزّنها في الكاش
    // لأن حفظ نماذج Eloquent مسلسلة هش ويكسر عند فك التسلسل.
    $sections = once(fn () => Section::query()
        ->active()
        ->ordered()
        ->with('activeCategories')
        ->get());

    $current = request()->path();
@endphp

<header
    x-data="{ open: false, scrolled: false }"
    x-on:scroll.window="scrolled = window.scrollY > 20"
    x-on:livewire:navigated.window="open = false"
    x-on:keydown.escape.window="open = false"
    class="sticky top-0 z-50 transition-colors duration-300 border-b"
    x-bind:class="scrolled || open
        ? 'bg-white/90 dark:bg-ink-950/90 backdrop-blur-lg border-ink-200 dark:border-ink-800'
        : 'bg-transparent border-transparent'"
>
    <div class="px-4 mx-auto max-w-7xl sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16 lg:h-20">

            {{-- الشعار --}}
            <a
                href="{{ route('home') }}"
                wire:navigate
                class="flex items-center gap-2.5 shrink-0 group"
                aria-label="{{ config('site.owner_name') }} — الصفحة الرئيسية"
            >
                {{-- الشعار صورة لا أيقونة مجرّدة: الرابط يحمل اسم المالك في aria-label،
                     فالصورة زخرفية ونصّها البديل فارغ حتى لا يُقرأ الاسم مرّتين. --}}
                <img src="/images/logo.png" alt="" width="36" height="36" class="size-9 shrink-0" aria-hidden="true">
                <span class="flex flex-col leading-none">
                    <span class="text-base font-extrabold text-ink-900 dark:text-ink-50">{{ config('site.owner_name') }}</span>
                    <span class="text-[11px] font-normal text-ink-500 dark:text-ink-400">مصور فوتوغرافي — {{ config('site.location.city') }}</span>
                </span>
            </a>

            {{-- التنقّل على الشاشات الكبيرة --}}
            <nav class="items-center hidden gap-1 lg:flex" aria-label="التنقّل الرئيسي">
                <x-site.nav-link :href="route('home')" :active="$current === '/'">الرئيسية</x-site.nav-link>
                <x-site.nav-link :href="route('portfolio')" :active="str_starts_with($current, 'portfolio')">المعرض</x-site.nav-link>

                @foreach ($sections as $section)
                    @if ($section->activeCategories->isNotEmpty())
                        {{-- قسم له أقسام فرعية — قائمة منسدلة --}}
                        <div
                            x-data="{ menu: false }"
                            x-on:mouseenter="menu = true"
                            x-on:mouseleave="menu = false"
                            class="relative"
                        >
                            <a
                                href="{{ $section->url() }}"
                                wire:navigate
                                x-on:click="menu = false"
                                class="flex items-center gap-1 rounded-lg px-3 py-2 text-sm font-bold transition-colors {{ str_starts_with($current, $section->slug) ? 'text-brand-600 dark:text-brand-400' : 'text-ink-700 hover:text-ink-950 dark:text-ink-300 dark:hover:text-white' }}"
                                x-bind:aria-expanded="menu"
                            >
                                {{ $section->name }}
                                <x-icon name="chevron-down" :size="14" class="transition-transform" x-bind:class="menu && 'rotate-180'" />
                            </a>

                            <div
                                x-show="menu"
                                x-cloak
                                x-transition:enter="transition ease-smooth duration-200"
                                x-transition:enter-start="opacity-0 -translate-y-1"
                                x-transition:enter-end="opacity-100 translate-y-0"
                                x-transition:leave="transition ease-in duration-100"
                                x-transition:leave-start="opacity-100"
                                x-transition:leave-end="opacity-0"
                                class="absolute z-50 pt-2 start-0 w-72"
                            >
                                <div class="p-2 bg-white border shadow-xl rounded-2xl border-ink-200 shadow-black/5 dark:border-ink-800 dark:bg-ink-900">
                                    @foreach ($section->activeCategories as $category)
                                        <a
                                            href="{{ $category->url() }}"
                                            wire:navigate
                                            class="flex items-start gap-3 p-3 transition-colors rounded-xl hover:bg-ink-50 dark:hover:bg-ink-800"
                                        >
                                            <span class="mt-0.5 flex size-8 items-center justify-center rounded-lg bg-brand-50 text-brand-600 dark:bg-brand-950 dark:text-brand-400">
                                                <x-icon :name="$category->icon" :size="16" />
                                            </span>
                                            <span class="flex flex-col gap-0.5">
                                                <span class="text-sm font-bold text-ink-900 dark:text-ink-100">{{ $category->name }}</span>
                                                <span class="text-xs text-ink-500 dark:text-ink-400">{{ $category->tagline }}</span>
                                            </span>
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @else
                        <x-site.nav-link :href="$section->url()" :active="str_starts_with($current, $section->slug)">
                            {{ $section->name }}
                        </x-site.nav-link>
                    @endif
                @endforeach

                <x-site.nav-link :href="route('about')" :active="$current === 'about'">نبذة</x-site.nav-link>
            </nav>

            {{-- الإجراءات --}}
            <div class="flex items-center gap-2">
                <x-site.search />
                <x-site.theme-toggle />

                <a
                    href="{{ route('contact') }}"
                    wire:navigate
                    class="hidden rounded-xl bg-ink-900 px-4 py-2.5 text-sm font-bold text-white transition-colors hover:bg-ink-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-500 sm:inline-flex dark:bg-brand-500 dark:text-ink-950 dark:hover:bg-brand-400"
                >
                    احجز موعد
                </a>

                <button
                    type="button"
                    x-on:click="open = !open"
                    class="inline-flex items-center justify-center transition-colors rounded-lg size-10 text-ink-700 hover:bg-ink-100 lg:hidden dark:text-ink-300 dark:hover:bg-ink-800"
                    x-bind:aria-expanded="open"
                    aria-controls="mobile-menu"
                    aria-label="القائمة"
                >
                    <template x-if="!open"><x-icon name="menu" :size="22" /></template>
                    <template x-if="open"><x-icon name="close" :size="22" /></template>
                </button>
            </div>
        </div>
    </div>

    {{-- قائمة الجوال --}}
    <div
        id="mobile-menu"
        x-show="open"
        x-cloak
        x-collapse
        class="border-t lg:hidden border-ink-200 bg-white/95 backdrop-blur-lg dark:border-ink-800 dark:bg-ink-950/95"
    >
        <nav class="px-4 py-4 mx-auto space-y-1 max-w-7xl sm:px-6" aria-label="التنقّل على الجوال">
            <x-site.mobile-link :href="route('home')" icon="home">الرئيسية</x-site.mobile-link>
            <x-site.mobile-link :href="route('portfolio')" icon="images">المعرض</x-site.mobile-link>

            @foreach ($sections as $section)
                <x-site.mobile-link :href="$section->url()" :icon="$section->icon">{{ $section->name }}</x-site.mobile-link>

                @if ($section->activeCategories->isNotEmpty())
                    <div class="border-s-2 ps-4 ms-5 border-ink-200 dark:border-ink-800">
                        @foreach ($section->activeCategories as $category)
                            <a
                                href="{{ $category->url() }}"
                                wire:navigate
                                class="block px-3 py-2 text-sm transition-colors rounded-lg text-ink-600 hover:bg-ink-50 hover:text-ink-900 dark:text-ink-400 dark:hover:bg-ink-800 dark:hover:text-ink-100"
                            >
                                {{ $category->name }}
                            </a>
                        @endforeach
                    </div>
                @endif
            @endforeach

            <x-site.mobile-link :href="route('about')" icon="user">نبذة</x-site.mobile-link>
            <x-site.mobile-link :href="route('faq')" icon="help">أسئلة شائعة</x-site.mobile-link>
            <x-site.mobile-link :href="route('contact')" icon="phone">التواصل والحجز</x-site.mobile-link>
        </nav>
    </div>
</header>
