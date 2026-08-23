@props([
    'title',
    'tagline' => null,
    'description' => null,
    'icon' => null,
    'breadcrumbs' => [],
])

<header class="relative overflow-hidden border-b border-ink-200 bg-ink-50 dark:border-ink-800 dark:bg-ink-900">
    {{-- نقش خفيف يمنع الخلفية من أن تبدو مسطّحة تمامًا --}}
    <div class="absolute inset-0 opacity-40 dark:opacity-20" aria-hidden="true">
        <svg class="size-full" xmlns="http://www.w3.org/2000/svg">
            <defs>
                <pattern id="ph-grid" width="32" height="32" patternUnits="userSpaceOnUse">
                    <path d="M0 32V0h32" fill="none" stroke="currentColor" stroke-width="0.5" class="text-ink-300 dark:text-ink-700" />
                </pattern>
            </defs>
            <rect width="100%" height="100%" fill="url(#ph-grid)" />
        </svg>
    </div>

    <div class="relative px-4 py-12 mx-auto max-w-7xl sm:px-6 lg:px-8 lg:py-16">
        @if ($breadcrumbs !== [])
            <x-site.breadcrumbs :items="$breadcrumbs" class="mb-6" />
        @endif

        <div class="max-w-3xl">
            @if ($icon)
                <span class="mb-5 inline-flex size-12 items-center justify-center rounded-2xl bg-brand-500 text-ink-950">
                    <x-icon :name="$icon" :size="24" :stroke="2" />
                </span>
            @endif

            @if ($tagline)
                <p class="mb-2 text-sm font-bold text-brand-600 dark:text-brand-400">{{ $tagline }}</p>
            @endif

            <h1 class="text-3xl font-extrabold leading-tight text-balance text-ink-900 sm:text-4xl lg:text-5xl dark:text-ink-50">
                {{ $title }}
            </h1>

            @if ($description)
                <p class="mt-5 text-base leading-8 text-ink-600 sm:text-lg dark:text-ink-400">
                    {{ $description }}
                </p>
            @endif

            @if (isset($actions))
                <div class="flex flex-wrap gap-3 mt-8">
                    {{ $actions }}
                </div>
            @endif
        </div>
    </div>
</header>
