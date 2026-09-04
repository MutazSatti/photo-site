{{-- ================= آراء العملاء ================= --}}
@if ($this->testimonials->isNotEmpty())
    <section data-block="testimonials" class="border-y border-ink-200 bg-ink-50 dark:border-ink-800 dark:bg-ink-900">
        <div class="px-4 py-16 mx-auto max-w-7xl sm:px-6 lg:px-8">
            <h2 class="text-2xl font-extrabold text-ink-900 sm:text-3xl dark:text-ink-50">آراء العملاء</h2>

            <div class="grid gap-5 mt-10 lg:grid-cols-3">
                @foreach ($this->testimonials as $testimonial)
                    <figure class="flex flex-col p-6 bg-white border rounded-2xl border-ink-200 dark:border-ink-800 dark:bg-ink-950">
                        <div class="flex items-center justify-between gap-3">
                            <div class="flex gap-0.5 text-brand-500" role="img" aria-label="تقييم {{ $testimonial->rating }} من 5">
                                @for ($i = 0; $i < $testimonial->rating; $i++)
                                    <x-icon name="star-filled" :size="15" />
                                @endfor
                            </div>

                            {{-- الإفصاح عن المصدر يقوّي المصداقية: الزائر يعرف أن الرأي قابل للتحقق --}}
                            @if ($testimonial->isFromGoogle())
                                <span class="inline-flex items-center gap-1 text-[11px] font-bold text-ink-500 dark:text-ink-400">
                                    <x-icon name="google" :size="12" />
                                    من تقييمات Google
                                </span>
                            @endif
                        </div>

                        <blockquote class="mt-4 text-sm leading-8 text-ink-700 grow dark:text-ink-300">
                            {{ $testimonial->content }}
                        </blockquote>

                        <figcaption class="pt-4 mt-5 border-t border-ink-200 dark:border-ink-800">
                            <span class="block text-sm font-extrabold text-ink-900 dark:text-ink-100">{{ $testimonial->name }}</span>
                            @if ($testimonial->role)
                                <span class="block mt-0.5 text-xs text-ink-500 dark:text-ink-400">{{ $testimonial->role }}</span>
                            @endif
                        </figcaption>
                    </figure>
                @endforeach
            </div>

            @if ($url = setting('google_reviews_url'))
                <div class="mt-8">
                    <a
                        href="{{ $url }}"
                        target="_blank"
                        rel="noopener nofollow"
                        class="inline-flex items-center gap-2 text-sm font-bold transition-colors text-ink-700 hover:text-brand-600 dark:text-ink-300 dark:hover:text-brand-400"
                    >
                        <x-icon name="google" :size="15" />
                        شاهد كل التقييمات على Google
                        <x-icon name="arrow-left" :size="15" />
                    </a>
                </div>
            @endif
        </div>
    </section>
@endif

