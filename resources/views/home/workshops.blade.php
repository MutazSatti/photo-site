{{-- ================= الورش التدريبية ================= --}}
@if ($this->workshops->isNotEmpty())
    <section data-block="workshops" class="border-y border-ink-200 bg-ink-50 dark:border-ink-800 dark:bg-ink-900">
        <div class="px-4 py-16 mx-auto max-w-7xl sm:px-6 lg:px-8 lg:py-20">
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div class="max-w-2xl">
                    <h2 class="text-2xl font-extrabold text-ink-900 sm:text-3xl dark:text-ink-50">ورش تدريبية</h2>
                    <p class="mt-3 text-base leading-8 text-ink-600 dark:text-ink-400">
                        تعلّم التصوير بشكل عملي — مقاعد محدودة لضمان المتابعة الفردية.
                    </p>
                </div>

                <x-ui.button href="{{ route('section.show', App\Models\Section::WORKSHOPS) }}" variant="outline" icon-after="arrow-left">
                    كل الورش
                </x-ui.button>
            </div>

            <div class="grid gap-6 mt-10 lg:grid-cols-3">
                @foreach ($this->workshops as $workshop)
                    <a
                        href="{{ $workshop->url() }}"
                        wire:navigate
                        class="flex flex-col p-6 transition-all bg-white border group rounded-2xl border-ink-200 hover:border-brand-400 hover:shadow-lg hover:shadow-black/5 dark:border-ink-800 dark:bg-ink-950 dark:hover:border-brand-600"
                    >
                        <div class="flex flex-wrap gap-2">
                            @if ($workshop->duration)
                                <x-ui.badge icon="clock">{{ $workshop->duration }}</x-ui.badge>
                            @endif
                            @if ($workshop->seats)
                                <x-ui.badge icon="users">{{ $workshop->seats }} مقعد</x-ui.badge>
                            @endif
                        </div>

                        <h3 class="mt-4 text-lg font-extrabold leading-8 transition-colors text-ink-900 group-hover:text-brand-600 dark:text-ink-50 dark:group-hover:text-brand-400">
                            {{ $workshop->title }}
                        </h3>

                        <p class="mt-2 text-sm leading-7 text-ink-600 line-clamp-3 grow dark:text-ink-400">
                            {{ $workshop->excerpt }}
                        </p>

                        @if ($workshop->price)
                            <p class="pt-4 mt-5 text-lg font-extrabold border-t text-brand-600 border-ink-200 dark:border-ink-800 dark:text-brand-400">
                                @money($workshop->price)
                            </p>
                        @endif
                    </a>
                @endforeach
            </div>
        </div>
    </section>
@endif

