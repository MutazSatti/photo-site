{{-- ================= الأقسام الرئيسية ================= --}}
<section data-block="sections" class="px-4 py-16 mx-auto max-w-7xl sm:px-6 lg:px-8 lg:py-20">
    <div class="max-w-2xl">
        <h2 class="text-2xl font-extrabold text-ink-900 sm:text-3xl dark:text-ink-50">أقسام المعرض</h2>
        <p class="mt-3 text-base leading-8 text-ink-600 dark:text-ink-400">
            كل قسم يحمل صوره وتفاصيله ولونه الخاص.
        </p>
    </div>

    <div class="grid gap-5 mt-10 sm:grid-cols-2 lg:grid-cols-4">
        @foreach ($this->sections as $section)
            <a
                href="{{ $section->url() }}"
                wire:navigate
                style="{{ $section->colorStyle() }}"
                class="sec-theme relative flex flex-col overflow-hidden p-6 pt-7 transition-all border group rounded-2xl border-ink-200 bg-white hover:sec-border hover:shadow-lg hover:shadow-black/5 dark:border-ink-800 dark:bg-ink-900 sec-stripe"
            >
                <span class="flex items-center justify-center transition-colors size-12 rounded-2xl sec-bg-soft sec-text group-hover:sec-bg-solid group-hover:text-white">
                    <x-icon :name="$section->icon" :size="24" :stroke="1.75" />
                </span>

                <h3 class="mt-5 text-lg font-extrabold text-ink-900 dark:text-ink-50">{{ $section->name }}</h3>
                <p class="mt-1.5 text-sm font-bold sec-text">{{ $section->tagline }}</p>

                <p class="mt-3 text-sm leading-7 text-ink-600 line-clamp-3 grow dark:text-ink-400">
                    {{ $section->description }}
                </p>

                <span class="mt-5 inline-flex items-center gap-1.5 text-sm font-bold text-ink-900 dark:text-ink-100">
                    استعرض القسم
                    <x-icon name="arrow-left" :size="15" class="transition-transform group-hover:-translate-x-1" />
                </span>
            </a>
        @endforeach
    </div>
</section>

