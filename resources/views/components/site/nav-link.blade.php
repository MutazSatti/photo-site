@props([
    'href',
    'active' => false,
])

<a
    href="{{ $href }}"
    wire:navigate
    @if ($active) aria-current="page" @endif
    {{ $attributes->class([
        'rounded-lg px-3 py-2 text-sm font-bold transition-colors',
        'text-brand-600 dark:text-brand-400' => $active,
        'text-ink-700 hover:text-ink-950 dark:text-ink-300 dark:hover:text-white' => ! $active,
    ]) }}
>
    {{ $slot }}
</a>
