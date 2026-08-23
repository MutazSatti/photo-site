@props([
    'title',
    'description' => null,
])

<div {{ $attributes->class('flex flex-wrap items-start justify-between gap-4 mb-6') }}>
    <div class="min-w-0">
        <h1 class="text-xl font-extrabold text-ink-900 sm:text-2xl dark:text-ink-50">{{ $title }}</h1>

        @if ($description)
            <p class="mt-1.5 text-sm leading-7 text-ink-500 dark:text-ink-400">{{ $description }}</p>
        @endif
    </div>

    @if (isset($actions))
        <div class="flex flex-wrap gap-2">{{ $actions }}</div>
    @endif
</div>
