@props(['icon' => 20])

@php
    use App\Models\Media;
    use App\Models\Setting;

    $logo = Media::logo();
    $maxHeight = (int) Setting::get('logo_max_height', 40);

    // فلتر الوضع الداكن: يُترك الشعار على لونه حيث يناسب الخلفية،
    // ويُقلب إلى الطرف الآخر حيث لا يناسبها. brightness-0 يوحّد أي لون إلى
    // أسود خالص أولًا، فيخرج القلب أبيض نقيًا لا رماديًا. أدوات Tailwind
    // القياسية هنا لا الصيغة الاعتباطية، لأن الأخيرة تفقد قيمة invert.
    $adaptClass = '';

    if (Setting::get('logo_adapt_dark') === '1') {
        $adaptClass = Setting::get('logo_base_color', 'black') === 'white'
            ? 'brightness-0 dark:brightness-100'
            : 'dark:brightness-0 dark:invert';
    }
@endphp

{{--
    علامة الموقع. الشعار المرفوع يُعرض بنسبته الطبيعية مقيَّدًا بارتفاع أقصى،
    بلا حاوية أو خلفية. عند غيابه تُعرض أيقونة العدسة داخل مربّع بلون العلامة.
--}}

@if ($logo)
    <img
        src="{{ $logo->url('md') }}"
        alt="{{ $logo->altText() }}"
        width="{{ $logo->width }}"
        height="{{ $logo->height }}"
        style="max-height:{{ $maxHeight }}px;width:auto;height:auto"
        {{ $attributes->class(['shrink-0 object-contain', $adaptClass]) }}
    >
@else
    <span
        style="width:{{ $maxHeight }}px;height:{{ $maxHeight }}px"
        {{ $attributes->class(['flex shrink-0 items-center justify-center rounded-xl bg-brand-500 text-ink-950 transition-colors group-hover:bg-brand-400']) }}
    >
        <x-icon name="aperture" :size="$icon" :stroke="2" />
    </span>
@endif
