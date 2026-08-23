@props([
    'title' => null,
    'description' => null,
    'padded' => true,
])

<section {{ $attributes->class('bg-white border rounded-2xl border-ink-200 dark:border-ink-800 dark:bg-ink-900') }}>
    @if ($title)
        <header class="flex flex-wrap items-start justify-between gap-3 px-5 py-4 border-b border-ink-200 dark:border-ink-800">
            <div class="min-w-0">
                <h2 class="text-sm font-extrabold text-ink-900 dark:text-ink-100">{{ $title }}</h2>
                @if ($description)
                    <p class="mt-1 text-xs leading-6 text-ink-500 dark:text-ink-400">{{ $description }}</p>
                @endif
            </div>

            @if (isset($actions))
                <div class="flex gap-2">{{ $actions }}</div>
            @endif
        </header>
    @endif

    <div @class(['p-5' => $padded])>
        {{ $slot }}
    </div>
</section>
