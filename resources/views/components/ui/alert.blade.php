@props([
    'variant' => 'info',
    'title' => null,
])

@php
    $config = [
        'info' => ['icon' => 'info', 'class' => 'border-blue-200 bg-blue-50 text-blue-900 dark:border-blue-900 dark:bg-blue-950/50 dark:text-blue-200'],
        'success' => ['icon' => 'check-circle', 'class' => 'border-emerald-200 bg-emerald-50 text-emerald-900 dark:border-emerald-900 dark:bg-emerald-950/50 dark:text-emerald-200'],
        'warning' => ['icon' => 'alert', 'class' => 'border-amber-200 bg-amber-50 text-amber-900 dark:border-amber-900 dark:bg-amber-950/50 dark:text-amber-200'],
        'danger' => ['icon' => 'alert', 'class' => 'border-red-200 bg-red-50 text-red-900 dark:border-red-900 dark:bg-red-950/50 dark:text-red-200'],
    ];

    $current = $config[$variant] ?? $config['info'];
@endphp

<div {{ $attributes->class(['flex gap-3 rounded-xl border p-4', $current['class']]) }} role="alert">
    <span class="mt-0.5 shrink-0"><x-icon :name="$current['icon']" :size="18" /></span>

    <div class="text-sm leading-6">
        @if ($title)
            <p class="mb-1 font-extrabold">{{ $title }}</p>
        @endif
        {{ $slot }}
    </div>
</div>
