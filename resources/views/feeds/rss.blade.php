{{-- ترويسة XML تُكتب كنص مطبوع لأن كتابتها مباشرة تُفسَّر كوسم PHP مفتوح --}}
{!! '<'.'?xml version="1.0" encoding="UTF-8"?'.'>' !!}
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom" xmlns:media="http://search.yahoo.com/mrss/">
    <channel>
        <title>{{ config('site.owner_name') }} — مصور فوتوغرافي في {{ config('site.location.city') }}</title>
        <link>{{ route('home') }}</link>
        <description>{{ setting('seo_description') }}</description>
        <language>ar</language>
        <lastBuildDate>{{ now()->toRfc2822String() }}</lastBuildDate>
        <atom:link href="{{ route('feed') }}" rel="self" type="application/rss+xml" />
@foreach ($posts as $post)
        <item>
            <title>{{ $post->title }}</title>
            <link>{{ $post->url() }}</link>
            <guid isPermaLink="true">{{ $post->url() }}</guid>
            <pubDate>{{ ($post->published_at ?? $post->created_at)->toRfc2822String() }}</pubDate>
            <category>{{ $post->section->name ?? '' }}</category>
            <description><![CDATA[{{ $post->metaDescription() }}]]></description>
@php $cover = $post->coverImage(); @endphp
@if ($cover)
            <media:content url="{{ $cover->url('lg') }}" medium="image" type="image/webp" />
@endif
        </item>
@endforeach
    </channel>
</rss>
