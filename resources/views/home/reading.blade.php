{{-- ================= مقالات ومنشورات ================= --}}
@if ($this->latestReading->isNotEmpty())
    <section data-block="reading" class="px-4 py-16 mx-auto max-w-7xl sm:px-6 lg:px-8 lg:py-20">
        <div class="max-w-2xl">
            <h2 class="text-2xl font-extrabold text-ink-900 sm:text-3xl dark:text-ink-50">اقرأ وتعلّم</h2>
            <p class="mt-3 text-base leading-8 text-ink-600 dark:text-ink-400">
                مقالات معمّقة ومنشورات تعليمية قصيرة — خلاصة تجربة ميدانية.
            </p>
        </div>

        <div class="grid gap-5 mt-10 sm:grid-cols-2">
            @foreach ($this->latestReading as $item)
                <a
                    href="{{ $item->url() }}"
                    wire:navigate
                    class="flex flex-col p-6 transition-all border group rounded-2xl border-ink-200 hover:border-brand-400 hover:bg-ink-50 dark:border-ink-800 dark:hover:border-brand-600 dark:hover:bg-ink-900"
                >
                    <div class="flex items-center gap-2 text-xs text-ink-500 dark:text-ink-400">
                        <x-ui.badge variant="brand">{{ $item->section->name }}</x-ui.badge>
                        <span class="inline-flex items-center gap-1">
                            <x-icon name="clock" :size="12" />
                            {{ $item->readingTime() }} دقائق قراءة
                        </span>
                    </div>

                    <h3 class="mt-4 text-lg font-extrabold leading-8 transition-colors text-ink-900 group-hover:text-brand-600 dark:text-ink-50 dark:group-hover:text-brand-400">
                        {{ $item->title }}
                    </h3>

                    <p class="mt-2 text-sm leading-7 text-ink-600 line-clamp-2 dark:text-ink-400">
                        {{ $item->excerpt }}
                    </p>
                </a>
            @endforeach
        </div>
    </section>
@endif

