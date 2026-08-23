@props([
    'invalid' => false,
])

<div class="relative">
    <select
        {{ $attributes->class([
            'w-full appearance-none rounded-xl border bg-white px-4 py-2.5 pe-10 text-sm text-ink-900 transition-colors',
            'focus:outline-none focus:ring-2 focus:ring-brand-500/40',
            'dark:bg-ink-900 dark:text-ink-100',
            'border-red-400 focus:border-red-500 dark:border-red-500' => $invalid,
            'border-ink-300 focus:border-brand-500 dark:border-ink-700' => ! $invalid,
        ]) }}
    >
        {{ $slot }}
    </select>

    <span class="absolute -translate-y-1/2 pointer-events-none end-3 top-1/2 text-ink-400">
        <x-icon name="chevron-down" :size="16" />
    </span>
</div>
