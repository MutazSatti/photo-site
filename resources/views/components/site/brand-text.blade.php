@props(['place' => 'header'])

@php
    use App\Models\Setting;

    // both = الاسم والوصف، name = الاسم وحده، none = بلا نص (الشعار كافٍ)
    $mode = Setting::get('brand_text_'.$place, $place === 'header' ? 'both' : 'name');
    $name = Setting::get('brand_name', config('site.owner_name'));
    $tagline = Setting::get('brand_tagline');
@endphp

@if ($mode !== 'none' && $name !== '')
    <span class="flex flex-col leading-none">
        <span class="text-base font-extrabold text-ink-900 dark:text-ink-50">{{ $name }}</span>

        @if ($mode === 'both' && $tagline)
            <span class="mt-1 text-[11px] font-normal text-ink-500 dark:text-ink-400">{{ $tagline }}</span>
        @endif
    </span>
@endif
