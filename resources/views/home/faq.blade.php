{{-- ================= الأسئلة الشائعة ================= --}}
@if ($this->faqs->isNotEmpty())
    <section data-block="faq" class="px-4 py-16 mx-auto max-w-4xl sm:px-6 lg:px-8 lg:py-20">
        <div class="text-center">
            <h2 class="text-2xl font-extrabold text-ink-900 sm:text-3xl dark:text-ink-50">أسئلة شائعة</h2>
            <p class="mt-3 text-base leading-8 text-ink-600 dark:text-ink-400">
                أكثر ما يُسأل عنه قبل الحجز.
            </p>
        </div>

        <div class="mt-10 space-y-3">
            @foreach ($this->faqs as $faq)
                <details class="p-5 transition-colors border group rounded-2xl border-ink-200 open:bg-ink-50 dark:border-ink-800 dark:open:bg-ink-900">
                    <summary class="flex cursor-pointer items-center justify-between gap-4 text-sm font-extrabold text-ink-900 marker:content-none dark:text-ink-100">
                        {{ $faq->question }}
                        <span class="transition-transform shrink-0 text-ink-400 group-open:rotate-180">
                            <x-icon name="chevron-down" :size="18" />
                        </span>
                    </summary>

                    <p class="mt-4 text-sm leading-8 text-ink-600 dark:text-ink-400">{{ $faq->answer }}</p>
                </details>
            @endforeach
        </div>

        <div class="mt-8 text-center">
            <x-ui.button href="{{ route('faq') }}" variant="ghost" icon-after="arrow-left">
                كل الأسئلة الشائعة
            </x-ui.button>
        </div>
    </section>
@endif
