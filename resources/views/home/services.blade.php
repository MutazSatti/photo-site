{{-- ================= خدمات التصوير ================= --}}
@php($servicesSection = $this->sections->firstWhere('slug', App\Models\Section::SERVICES))

<section data-block="services"
    class="sec-theme border-y border-ink-200 bg-ink-50 dark:border-ink-800 dark:bg-ink-900"
    style="{{ $servicesSection?->colorStyle() }}"
>
    <div class="px-4 py-16 mx-auto max-w-7xl sm:px-6 lg:px-8 lg:py-20">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div class="max-w-2xl">
                <h2 class="text-2xl font-extrabold text-ink-900 sm:text-3xl dark:text-ink-50">
                    خدمات التصوير في {{ config('site.location.city') }}
                </h2>
                <p class="mt-3 text-base leading-8 text-ink-600 dark:text-ink-400">
                    لكل تخصص متطلباته وأسلوبه في التغطية والتسليم.
                </p>
            </div>

            <x-ui.button href="{{ route('section.show', App\Models\Section::SERVICES) }}" variant="outline" icon-after="arrow-left">
                كل الخدمات
            </x-ui.button>
        </div>

        <div class="grid gap-5 mt-10 sm:grid-cols-2">
            @foreach ($this->serviceCategories as $category)
                <a
                    href="{{ $category->url() }}"
                    wire:navigate
                    class="flex items-start gap-5 p-6 transition-all bg-white border group rounded-2xl border-ink-200 hover:sec-border hover:shadow-lg hover:shadow-black/5 dark:border-ink-800 dark:bg-ink-950 "
                >
                    <span class="flex items-center justify-center transition-colors shrink-0 size-12 rounded-2xl sec-bg-soft sec-text group-hover:sec-bg-solid group-hover:text-white">
                        <x-icon :name="$category->icon" :size="22" />
                    </span>

                    <div class="min-w-0">
                        <div class="flex items-center gap-2">
                            <h3 class="text-base font-extrabold text-ink-900 dark:text-ink-50">{{ $category->name }}</h3>
                            @if ($category->posts_count)
                                <x-ui.badge>{{ $category->posts_count }}</x-ui.badge>
                            @endif
                        </div>

                        <p class="mt-1 text-sm font-bold sec-text">{{ $category->tagline }}</p>
                        <p class="mt-2 text-sm leading-7 text-ink-600 line-clamp-2 dark:text-ink-400">{{ $category->description }}</p>
                    </div>
                </a>
            @endforeach
        </div>
    </div>
</section>

