@props([
    'icon' => 'images',
    'title' => 'لا يوجد محتوى بعد',
    'description' => null,
])

<div {{ $attributes->class('flex flex-col items-center justify-center px-6 py-20 text-center border border-dashed rounded-2xl border-ink-300 dark:border-ink-700') }}>
    <span class="flex items-center justify-center mb-4 rounded-2xl size-14 bg-ink-100 text-ink-400 dark:bg-ink-800 dark:text-ink-500">
        <x-icon :name="$icon" :size="26" />
    </span>

    <h2 class="text-lg font-extrabold text-ink-800 dark:text-ink-200">{{ $title }}</h2>

    @if ($description)
        <p class="max-w-md mt-2 text-sm leading-7 text-ink-500 dark:text-ink-400">{{ $description }}</p>
    @endif

    @if (isset($actions))
        <div class="flex flex-wrap justify-center gap-3 mt-6">{{ $actions }}</div>
    @endif
</div>
