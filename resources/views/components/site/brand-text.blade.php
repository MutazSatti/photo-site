@props(['place' => 'header'])

@php
    use App\Models\Setting;

    // both = الاسم والوصف، name = الاسم وحده، none = بلا نص (الشعار كافٍ)
    $mode = Setting::get('brand_text_'.$place, $place === 'header' ? 'both' : 'name');

    // بلا قيمة افتراضية: الحقل الفارغ يعني الإخفاء عمدًا، ولو رجعنا
    // هنا إلى اسم من config لتعذّر إخفاء النص من لوحة التحكم أصلًا.
    $name = trim((string) Setting::get('brand_name'));
    $tagline = trim((string) Setting::get('brand_tagline'));

    $showName = $mode !== 'none' && $name !== '';
    $showTagline = $mode === 'both' && $tagline !== '';
@endphp

{{-- لا حاوية ولا فراغ متروك حين لا نص يُعرض — الشعار وحده. --}}

@if ($showName || $showTagline)
    <span class="flex flex-col leading-none">
        @if ($showName)
            <span class="text-base font-extrabold text-ink-900 dark:text-ink-50">{{ $name }}</span>
        @endif

        @if ($showTagline)
            <span @class([
                'text-[11px] font-normal text-ink-500 dark:text-ink-400',
                'mt-1' => $showName,
            ])>{{ $tagline }}</span>
        @endif
    </span>
@endif
