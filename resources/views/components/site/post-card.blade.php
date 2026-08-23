@props([
    'post',
    'eager' => false,
])

@php
    $cover = $post->coverImage();
    $imagesCount = $post->relationLoaded('media') ? $post->media->count() : 0;
@endphp

<article class="group">
    <a href="{{ $post->url() }}" wire:navigate class="block focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-brand-500 rounded-2xl">

        <div class="relative overflow-hidden bg-ink-100 rounded-2xl dark:bg-ink-800 aspect-4/3">
            <x-site.picture
                :media="$cover"
                variant="md"
                :eager="$eager"
                class="size-full transition-transform duration-700 ease-smooth group-hover:scale-105"
            />

            <div class="absolute inset-0 transition-opacity opacity-0 img-scrim group-hover:opacity-100"></div>

            @if ($imagesCount > 1)
                <span class="absolute flex items-center gap-1.5 rounded-full bg-black/55 px-2.5 py-1 text-xs font-bold text-white backdrop-blur-sm top-3 end-3">
                    <x-icon name="images" :size="13" />
                    {{ $imagesCount }}
                </span>
            @endif

            @if ($post->is_featured)
                <span class="absolute rounded-full bg-brand-500 px-2.5 py-1 text-xs font-bold text-ink-950 top-3 start-3">
                    مميّز
                </span>
            @endif
        </div>

        <div class="mt-4">
            <div class="flex flex-wrap items-center gap-2 mb-2 text-xs text-ink-500 dark:text-ink-400">
                @if ($post->category)
                    <span class="font-bold text-brand-600 dark:text-brand-400">{{ $post->category->name }}</span>
                    <span aria-hidden="true">·</span>
                @endif

                @if ($post->location)
                    <span class="inline-flex items-center gap-1">
                        <x-icon name="map-pin" :size="12" />
                        {{ $post->location }}
                    </span>
                @elseif ($post->published_at)
                    <span>{{ $post->published_at->translatedFormat('j F Y') }}</span>
                @endif
            </div>

            <h3 class="text-base font-extrabold leading-7 transition-colors text-ink-900 group-hover:text-brand-600 dark:text-ink-50 dark:group-hover:text-brand-400">
                {{ $post->title }}
            </h3>

            @if ($post->excerpt)
                <p class="mt-1.5 line-clamp-2 text-sm leading-6 text-ink-600 dark:text-ink-400">
                    {{ $post->excerpt }}
                </p>
            @endif

            @if ($post->price || $post->duration)
                <div class="flex flex-wrap items-center gap-2 mt-3">
                    @if ($post->duration)
                        <x-ui.badge icon="clock">{{ $post->duration }}</x-ui.badge>
                    @endif
                    @if ($post->price)
                        <x-ui.badge variant="brand">@money($post->price)</x-ui.badge>
                    @endif
                </div>
            @endif
        </div>
    </a>
</article>
