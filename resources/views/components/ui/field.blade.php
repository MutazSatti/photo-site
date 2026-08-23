@props([
    'label' => null,
    'for' => null,
    'hint' => null,
    'error' => null,
    'required' => false,
])

<div {{ $attributes->class('grid gap-2') }}>
    @if ($label)
        <label @if ($for) for="{{ $for }}" @endif class="text-sm font-bold text-ink-800 dark:text-ink-200">
            {{ $label }}
            @if ($required)
                <span class="text-red-600 dark:text-red-400" aria-hidden="true">*</span>
            @endif
        </label>
    @endif

    {{ $slot }}

    @if ($hint && ! $error)
        <p class="text-xs text-ink-500 dark:text-ink-400">{{ $hint }}</p>
    @endif

    @if ($error)
        <p class="flex items-center gap-1.5 text-xs font-bold text-red-600 dark:text-red-400">
            <x-icon name="alert" :size="14" />
            {{ $error }}
        </p>
    @endif
</div>
