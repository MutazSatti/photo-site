@props([
    'variant' => 'neutral',
    'icon' => null,
])

@php
    $variants = [
        'neutral' => 'bg-ink-100 text-ink-700 dark:bg-ink-800 dark:text-ink-300',
        'brand' => 'bg-brand-50 text-brand-700 dark:bg-brand-950 dark:text-brand-400',
        'success' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-400',
        'warning' => 'bg-amber-50 text-amber-700 dark:bg-amber-950 dark:text-amber-400',
        'danger' => 'bg-red-50 text-red-700 dark:bg-red-950 dark:text-red-400',
    ];
@endphp

<span {{ $attributes->class([
    'inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-bold',
    $variants[$variant] ?? $variants['neutral'],
]) }}>
    @if ($icon)<x-icon :name="$icon" :size="13" />@endif
    {{ $slot }}
</span>
