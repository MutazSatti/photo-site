@props([
    'href',
    'icon' => 'chevron-left',
])

<a
    href="{{ $href }}"
    wire:navigate
    {{ $attributes->class('flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-bold text-ink-800 transition-colors hover:bg-ink-100 dark:text-ink-200 dark:hover:bg-ink-800') }}
>
    <span class="flex items-center justify-center rounded-lg size-8 bg-ink-100 text-ink-600 dark:bg-ink-800 dark:text-ink-400">
        <x-icon :name="$icon" :size="16" />
    </span>
    {{ $slot }}
</a>
