@props([
    'media' => null,
    'variant' => 'md',
    'sizes' => '(min-width: 1024px) 33vw, (min-width: 640px) 50vw, 100vw',
    'eager' => false,
    'class' => '',
])

{{--
    صورة WebP متجاوبة. الأبعاد تُكتب صراحة ليحجز المتصفح المساحة قبل التحميل
    فلا يقفز التخطيط. عند غياب الصورة يُعرض بديل رمادي بدل فراغ مكسور.
--}}

@if ($media)
    <img
        src="{{ $media->url($variant) }}"
        @if ($media->srcset()) srcset="{{ $media->srcset() }}" sizes="{{ $sizes }}" @endif
        alt="{{ $media->altText() }}"
        width="{{ $media->width }}"
        height="{{ $media->height }}"
        loading="{{ $eager ? 'eager' : 'lazy' }}"
        decoding="{{ $eager ? 'sync' : 'async' }}"
        @if ($eager) fetchpriority="high" @endif
        {{ $attributes->class(['object-cover', $class]) }}
    >
@else
    <div {{ $attributes->class(['flex items-center justify-center bg-ink-100 text-ink-300 dark:bg-ink-800 dark:text-ink-600', $class]) }} aria-hidden="true">
        <x-icon name="image" :size="32" />
    </div>
@endif
