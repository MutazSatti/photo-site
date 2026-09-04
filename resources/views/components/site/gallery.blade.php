@props([
    'media' => collect(),
    'columns' => 3,
])

@php
    $items = collect($media)->values();

    $gridClass = match ((int) $columns) {
        2 => 'sm:grid-cols-2',
        4 => 'sm:grid-cols-2 lg:grid-cols-4',
        default => 'sm:grid-cols-2 lg:grid-cols-3',
    };

    /*
       نسبة البلاطة تتبع اتجاه الصور. تجميعات النشر عمودية (9:16)، وقصّها على
       نسبة أفقية يُظهر شريحة من وسطها لا الصورة. والحكم بالغالب لا بالأولى:
       مجموعة مختلطة تُقصّ على الشكل الذي يغلب عليها.

       العمودي يُقصّ على 3:4 لا 9:16 عمدًا: البلاطة الكاملة الطول تجعل صفّ
       ثلاث صور أطول من الشاشة. والعارض يفتح الصورة كاملةً على أي حال.
    */
    $portrait = $items->filter(fn ($m) => $m->width && $m->height && $m->height > $m->width * 1.2)->count();
    $tileAspect = $portrait * 2 > $items->count() ? 'aspect-3/4' : 'aspect-4/3';

    // بيانات خفيفة للعارض — روابط ونصوص فقط
    $lightboxData = $items->map(fn ($m) => [
        'src' => $m->url('lg'),
        'alt' => $m->altText(),
        'caption' => $m->caption,
        'width' => $m->width,
        'height' => $m->height,
    ])->values();
@endphp

@if ($items->isNotEmpty())
    <div
        x-data="{
            items: {{ Js::from($lightboxData) }},
            open: false,
            index: 0,
            show(i) { this.index = i; this.open = true; document.body.style.overflow = 'hidden'; },
            close() { this.open = false; document.body.style.overflow = ''; },
            next() { this.index = (this.index + 1) % this.items.length; },
            prev() { this.index = (this.index - 1 + this.items.length) % this.items.length; },
        }"
        x-on:keydown.escape.window="open && close()"
        x-on:keydown.arrow-left.window="open && next()"
        x-on:keydown.arrow-right.window="open && prev()"
        x-on:livewire:navigating.window="open && close()"
    >
        <div {{ $attributes->class(['grid grid-cols-1 gap-4', $gridClass]) }}>
            @foreach ($items as $index => $item)
                <button
                    type="button"
                    x-on:click="show({{ $index }})"
                    class="relative group overflow-hidden rounded-2xl bg-ink-100 dark:bg-ink-800 {{ $tileAspect }} focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-500"
                    aria-label="تكبير الصورة: {{ $item->altText() }}"
                >
                    <x-site.picture
                        :media="$item"
                        variant="md"
                        :eager="$index < 3"
                        sizes="(min-width: 1024px) 33vw, (min-width: 640px) 50vw, 100vw"
                        class="size-full transition-transform duration-700 ease-smooth group-hover:scale-105"
                    />

                    <span class="absolute inset-0 flex items-center justify-center transition-opacity opacity-0 bg-black/35 group-hover:opacity-100">
                        <span class="flex items-center justify-center text-white rounded-full size-11 bg-white/15 backdrop-blur-sm">
                            <x-icon name="zoom-in" :size="20" />
                        </span>
                    </span>

                    @if ($item->caption)
                        <span class="absolute inset-x-0 bottom-0 p-3 text-xs font-bold text-right text-white transition-opacity opacity-0 img-scrim group-hover:opacity-100">
                            {{ $item->caption }}
                        </span>
                    @endif
                </button>
            @endforeach
        </div>

        {{-- عارض الصور --}}
        <div
            x-show="open"
            x-cloak
            x-transition:enter="transition ease-smooth duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 z-100 flex items-center justify-center bg-ink-950/95 backdrop-blur-sm"
            role="dialog"
            aria-modal="true"
            aria-label="عارض الصور"
        >
            <button
                type="button"
                x-on:click="close()"
                class="absolute z-10 flex items-center justify-center text-white transition-colors rounded-full top-4 end-4 size-11 bg-white/10 hover:bg-white/20"
                aria-label="إغلاق العارض"
            >
                <x-icon name="close" :size="20" />
            </button>

            <template x-if="items.length > 1">
                <div>
                    <button
                        type="button"
                        x-on:click="prev()"
                        class="absolute z-10 flex items-center justify-center text-white -translate-y-1/2 rounded-full start-4 top-1/2 size-11 bg-white/10 hover:bg-white/20"
                        aria-label="الصورة السابقة"
                    >
                        <x-icon name="chevron-right" :size="20" />
                    </button>

                    <button
                        type="button"
                        x-on:click="next()"
                        class="absolute z-10 flex items-center justify-center text-white -translate-y-1/2 rounded-full end-4 top-1/2 size-11 bg-white/10 hover:bg-white/20"
                        aria-label="الصورة التالية"
                    >
                        <x-icon name="chevron-left" :size="20" />
                    </button>
                </div>
            </template>

            <figure class="flex flex-col items-center max-w-6xl px-4 max-h-[90vh]" x-on:click.outside="close()">
                <img
                    x-bind:src="items[index].src"
                    x-bind:alt="items[index].alt"
                    x-bind:width="items[index].width"
                    x-bind:height="items[index].height"
                    class="max-h-[78vh] w-auto rounded-lg object-contain"
                >

                <figcaption class="mt-4 text-sm text-center text-white/80">
                    <span x-text="items[index].caption || items[index].alt"></span>
                    <template x-if="items.length > 1">
                        <span class="block mt-1 text-xs text-white/50">
                            <span x-text="index + 1"></span> من <span x-text="items.length"></span>
                        </span>
                    </template>
                </figcaption>
            </figure>
        </div>
    </div>
@endif
