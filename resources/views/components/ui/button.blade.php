@props([
    'variant' => 'primary',
    'size' => 'md',
    'href' => null,
    'icon' => null,
    'iconAfter' => null,
    'navigate' => true,
])

@php
    $base = 'inline-flex items-center justify-center gap-2 font-bold transition-colors rounded-xl focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-500 disabled:opacity-50 disabled:pointer-events-none';

    $variants = [
        'primary' => 'bg-ink-900 text-white hover:bg-ink-800 dark:bg-brand-500 dark:text-ink-950 dark:hover:bg-brand-400',
        'brand' => 'bg-brand-500 text-ink-950 hover:bg-brand-400',
        'outline' => 'border border-ink-300 text-ink-800 hover:bg-ink-50 dark:border-ink-700 dark:text-ink-200 dark:hover:bg-ink-800',
        // للأسطح الداكنة كواجهات الصفحات فوق الصور — الحدّ والنص يقرآن على الصورة
        'outline-light' => 'border border-white/40 text-white hover:bg-white/10',
        'ghost' => 'text-ink-700 hover:bg-ink-100 dark:text-ink-300 dark:hover:bg-ink-800',
        'danger' => 'bg-red-600 text-white hover:bg-red-700',
        'whatsapp' => 'bg-[#25D366] text-white hover:bg-[#20bd5a]',
    ];

    $sizes = [
        'sm' => 'px-3 py-1.5 text-xs',
        'md' => 'px-4 py-2.5 text-sm',
        'lg' => 'px-6 py-3.5 text-base',
    ];

    $classes = implode(' ', [$base, $variants[$variant] ?? $variants['primary'], $sizes[$size] ?? $sizes['md']]);
@endphp

@if ($href)
    <a href="{{ $href }}" @if ($navigate) wire:navigate @endif {{ $attributes->class($classes) }}>
        @if ($icon)<x-icon :name="$icon" :size="$size === 'lg' ? 20 : 16" />@endif
        {{ $slot }}
        @if ($iconAfter)<x-icon :name="$iconAfter" :size="$size === 'lg' ? 20 : 16" />@endif
    </a>
@else
    <button {{ $attributes->merge(['type' => 'button'])->class($classes) }}>
        @if ($icon)<x-icon :name="$icon" :size="$size === 'lg' ? 20 : 16" />@endif
        {{ $slot }}
        @if ($iconAfter)<x-icon :name="$iconAfter" :size="$size === 'lg' ? 20 : 16" />@endif
    </button>
@endif
