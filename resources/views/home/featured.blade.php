{{-- ================= أعمال مختارة ================= --}}
@php $works = $this->featured->isNotEmpty() ? $this->featured : $this->latestWorks; @endphp

@if ($works->isNotEmpty())
    <section data-block="featured" class="px-4 py-16 mx-auto max-w-7xl sm:px-6 lg:px-8 lg:py-20">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div class="max-w-2xl">
                <h2 class="text-2xl font-extrabold text-ink-900 sm:text-3xl dark:text-ink-50">أعمال مختارة</h2>
                <p class="mt-3 text-base leading-8 text-ink-600 dark:text-ink-400">
                    نماذج من التغطيات الأخيرة — مناسبات ومؤتمرات وعقارات وبرامج تدريبية.
                </p>
            </div>

            <x-ui.button href="{{ route('portfolio') }}" variant="outline" icon-after="arrow-left">
                المعرض الكامل
            </x-ui.button>
        </div>

        <div class="grid gap-8 mt-10 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($works as $post)
                <x-site.post-card :post="$post" :eager="$loop->index < 3" />
            @endforeach
        </div>
    </section>
@endif

