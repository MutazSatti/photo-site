@props([
    'media' => null,
    'variant' => 'md',
    'sizes' => '(min-width: 1024px) 33vw, (min-width: 640px) 50vw, 100vw',
    'eager' => false,
    'fit' => 'cover',
    'class' => '',
])

{{--
    صورة WebP متجاوبة. الأبعاد تُكتب صراحة ليحجز المتصفح المساحة قبل التحميل
    فلا يقفز التخطيط. عند غياب الصورة يُعرض بديل رمادي بدل فراغ مكسور.

    الصنف يُكتب كاملًا في كل فرع لا يُركّب من متغيّر: ماسح Tailwind يقرأ النص
    كما هو، وصنف مبنيّ في PHP لا يظهر له فلا يُولَّد له أي CSS.
--}}

@php($fitClass = $fit === 'contain' ? 'object-contain' : 'object-cover')

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
        {{ $attributes->class([$fitClass, $class]) }}
    >
@else
    <div {{ $attributes->class(['flex items-center justify-center bg-ink-100 text-ink-300 dark:bg-ink-800 dark:text-ink-600', $class]) }} aria-hidden="true">
        <x-icon name="image" :size="32" />
    </div>
@endif
