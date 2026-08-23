@props([
    'items' => [],
])

@php
    // الرئيسية دائمًا أول عنصر — تطابق ما يُنشر في بيانات BreadcrumbList
    $all = array_merge([['label' => 'الرئيسية', 'url' => route('home')]], $items);
@endphp

<nav {{ $attributes }} aria-label="مسار التنقّل">
    <ol class="flex flex-wrap items-center gap-1.5 text-xs text-ink-500 dark:text-ink-400">
        @foreach ($all as $index => $item)
            <li class="flex items-center gap-1.5">
                @if (! empty($item['url']) && ! $loop->last)
                    <a href="{{ $item['url'] }}" wire:navigate class="transition-colors hover:text-brand-600 dark:hover:text-brand-400">
                        {{ $item['label'] }}
                    </a>
                @else
                    <span class="font-bold text-ink-700 dark:text-ink-300" aria-current="page">{{ $item['label'] }}</span>
                @endif

                @unless ($loop->last)
                    <span class="text-ink-300 dark:text-ink-600" aria-hidden="true">
                        <x-icon name="chevron-left" :size="12" />
                    </span>
                @endunless
            </li>
        @endforeach
    </ol>
</nav>
