@props([
    'rows' => 4,
    'invalid' => false,
])

<textarea
    rows="{{ $rows }}"
    {{ $attributes->class([
        'w-full rounded-xl border bg-white px-4 py-3 text-sm leading-7 text-ink-900 transition-colors',
        'placeholder:text-ink-400 focus:outline-none focus:ring-2 focus:ring-brand-500/40',
        'dark:bg-ink-900 dark:text-ink-100 dark:placeholder:text-ink-500',
        'border-red-400 focus:border-red-500 dark:border-red-500' => $invalid,
        'border-ink-300 focus:border-brand-500 dark:border-ink-700' => ! $invalid,
    ]) }}
>{{ $slot }}</textarea>
