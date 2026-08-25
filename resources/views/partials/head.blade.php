@php
    $seo = seo();
    $ownerName = config('site.owner_name');
    $city = config('site.location.city');
@endphp

<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
<meta name="theme-color" content="#111110" media="(prefers-color-scheme: dark)">
<meta name="theme-color" content="#fdf8ed" media="(prefers-color-scheme: light)">

<title>{{ $seo->fullTitle() }}</title>

<meta name="description" content="{{ $seo->metaDescription() }}">
<meta name="keywords" content="{{ setting('seo_keywords') }}">
<meta name="author" content="{{ $ownerName }}">
<meta name="robots" content="{{ $seo->robots }}">
<link rel="canonical" href="{{ $seo->canonicalUrl() }}">

{{-- إشارات جغرافية — تساعد أدوات البحث على ربط الموقع بالمدينة الصحيحة --}}
<meta name="geo.region" content="{{ config('site.location.country') }}-02">
<meta name="geo.placename" content="{{ $city }}">
<meta name="geo.position" content="{{ config('site.location.latitude') }};{{ config('site.location.longitude') }}">
<meta name="ICBM" content="{{ config('site.location.latitude') }}, {{ config('site.location.longitude') }}">

{{-- Open Graph --}}
<meta property="og:type" content="{{ $seo->type }}">
<meta property="og:site_name" content="{{ $ownerName }}">
<meta property="og:locale" content="ar_SA">
<meta property="og:title" content="{{ $seo->fullTitle() }}">
<meta property="og:description" content="{{ $seo->metaDescription() }}">
<meta property="og:url" content="{{ $seo->canonicalUrl() }}">
<meta property="og:image" content="{{ $seo->socialImage() }}">
<meta property="og:image:alt" content="{{ $seo->title ?: $ownerName }}">
@if ($ogSize = $seo->socialImageDimensions())
    {{-- واتساب وتيليجرام يعرضان مصغَّرًا صغيرًا بلا هذه الأبعاد --}}
    <meta property="og:image:width" content="{{ $ogSize['width'] }}">
    <meta property="og:image:height" content="{{ $ogSize['height'] }}">
    <meta property="og:image:type" content="{{ $ogSize['type'] }}">
@endif

{{-- Twitter / X --}}
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $seo->fullTitle() }}">
<meta name="twitter:description" content="{{ $seo->metaDescription() }}">
<meta name="twitter:image" content="{{ $seo->socialImage() }}">
<meta name="twitter:image:alt" content="{{ $seo->title ?: $ownerName }}">

{{-- أيقونة الموقع: المرفوعة من لوحة التحكم إن وُجدت، وإلا الملفات الثابتة --}}
@if ($favicon = \App\Models\Media::favicon())
    <link rel="icon" href="{{ $favicon->url('thumb') }}" type="image/webp">
    <link rel="apple-touch-icon" href="{{ $favicon->url('thumb') }}">
@else
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <link rel="icon" href="/favicon.ico" sizes="32x32">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png">
@endif
<link rel="manifest" href="/site.webmanifest">
<link rel="alternate" type="application/rss+xml" title="{{ $ownerName }} — أحدث الأعمال" href="{{ route('feed') }}">

{{-- تحميل مبكر لأوزان الخط الأكثر استخدامًا لتفادي ومضة النص --}}
<link rel="preload" href="/fonts/almarai/almarai-400-arabic.woff2" as="font" type="font/woff2" crossorigin>
<link rel="preload" href="/fonts/almarai/almarai-700-arabic.woff2" as="font" type="font/woff2" crossorigin>

@vite(['resources/css/app.css', 'resources/js/app.js'])

{{-- تجاوز لون العلامة — بعد @vite ليعلو على تدرّج app.css --}}
@include('partials.brand-color')

{{-- البيانات المهيكلة — ما تقرأه محرّكات البحث ومساعدات الذكاء الاصطناعي --}}
<script type="application/ld+json">{!! $seo->jsonLd() !!}</script>
