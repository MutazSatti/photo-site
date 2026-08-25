@props(['size' => 36, 'icon' => 20])

@php
    $logo = \App\Models\Media::logo();
@endphp

{{--
    علامة الموقع. عند رفع شعار من الإعدادات يُعرض كما هو على خلفية شفافة،
    وإلا فأيقونة العدسة داخل مربّع بلون العلامة كما كان التصميم الأصلي.
--}}

@if ($logo)
    <img
        src="{{ $logo->url('thumb') }}"
        alt="{{ $logo->altText() }}"
        width="{{ $size }}"
        height="{{ $size }}"
        style="width:{{ $size }}px;height:{{ $size }}px"
        {{ $attributes->class(['shrink-0 rounded-xl object-contain']) }}
    >
@else
    <span
        style="width:{{ $size }}px;height:{{ $size }}px"
        {{ $attributes->class(['flex shrink-0 items-center justify-center rounded-xl bg-brand-500 text-ink-950 transition-colors group-hover:bg-brand-400']) }}
    >
        <x-icon name="aperture" :size="$icon" :stroke="2" />
    </span>
@endif
