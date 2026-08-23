@props([
    'label',
    'value',
    'icon' => 'grid',
    'href' => null,
    'hint' => null,
])

@php
    $classes = [
        'flex items-start gap-4 rounded-2xl border border-ink-200 bg-white p-5 dark:border-ink-800 dark:bg-ink-900',
        'transition-colors hover:border-brand-400 dark:hover:border-brand-600' => (bool) $href,
    ];
@endphp

@if ($href)
    <a href="{{ $href }}" wire:navigate {{ $attributes->class($classes) }}>
        <span class="flex items-center justify-center shrink-0 size-11 rounded-xl bg-brand-50 text-brand-600 dark:bg-brand-950 dark:text-brand-400">
            <x-icon :name="$icon" :size="20" />
        </span>

        <div class="min-w-0">
            <p class="text-2xl font-extrabold text-ink-900 dark:text-ink-50" dir="ltr">{{ $value }}</p>
            <p class="mt-0.5 text-sm text-ink-500 dark:text-ink-400">{{ $label }}</p>
            @if ($hint)
                <p class="mt-1 text-xs text-ink-400">{{ $hint }}</p>
            @endif
        </div>
    </a>
@else
    <div {{ $attributes->class($classes) }}>
        <span class="flex items-center justify-center shrink-0 size-11 rounded-xl bg-brand-50 text-brand-600 dark:bg-brand-950 dark:text-brand-400">
            <x-icon :name="$icon" :size="20" />
        </span>

        <div class="min-w-0">
            <p class="text-2xl font-extrabold text-ink-900 dark:text-ink-50" dir="ltr">{{ $value }}</p>
            <p class="mt-0.5 text-sm text-ink-500 dark:text-ink-400">{{ $label }}</p>
            @if ($hint)
                <p class="mt-1 text-xs text-ink-400">{{ $hint }}</p>
            @endif
        </div>
    </div>
@endif
