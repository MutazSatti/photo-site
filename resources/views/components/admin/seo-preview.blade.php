@props([
    'title' => '',
    'description' => '',
    'url' => '',
    'image' => null,
    'siteName' => '',
])

@php
    $title = trim($title) ?: 'بلا عنوان';
    $description = trim($description);
    $host = parse_url($url, PHP_URL_HOST) ?: 'mutazsatti.com';
    $path = trim((string) parse_url($url, PHP_URL_PATH), '/');

    // حدود العرض الفعلية قبل القصّ بثلاث نقاط.
    $titleLimit = 60;
    $descLimit = 155;
@endphp

<div class="grid gap-5 lg:grid-cols-2">

    {{-- ═══ نتيجة البحث ═══ --}}
    <div class="p-5 border rounded-2xl border-ink-200 bg-white dark:border-ink-800 dark:bg-ink-950">
        <div class="flex items-center gap-2 mb-4">
            <x-icon name="search" :size="15" class="text-ink-400" />
            <span class="text-xs font-bold tracking-wide text-ink-500 dark:text-ink-400">نتيجة بحث Google</span>
        </div>

        <div dir="rtl" class="font-sans">
            <div class="flex items-center gap-2 mb-1">
                <span class="flex items-center justify-center border rounded-full size-6 border-ink-200 dark:border-ink-700">
                    <x-icon name="camera" :size="12" class="text-ink-500" />
                </span>
                <span class="flex flex-col leading-tight">
                    <span class="text-xs text-ink-800 dark:text-ink-200">{{ $siteName }}</span>
                    <span class="text-[11px] text-ink-500 dark:text-ink-400" dir="ltr">{{ $host }}{{ $path ? ' › '.$path : '' }}</span>
                </span>
            </div>

            <p class="text-[19px] leading-7 text-[#1a0dab] dark:text-[#8ab4f8] truncate">{{ $title }}</p>

            <p class="text-[13px] leading-6 text-ink-600 dark:text-ink-400">
                {{ \Illuminate\Support\Str::limit($description, $descLimit) ?: 'لا وصف — ستختار Google مقتطفًا من الصفحة.' }}
            </p>
        </div>
    </div>

    {{-- ═══ واتساب ═══ --}}
    <div class="p-5 border rounded-2xl border-ink-200 bg-white dark:border-ink-800 dark:bg-ink-950">
        <div class="flex items-center gap-2 mb-4">
            <x-icon name="whatsapp" :size="15" class="text-[#25D366]" />
            <span class="text-xs font-bold tracking-wide text-ink-500 dark:text-ink-400">مشاركة في واتساب</span>
        </div>

        <div class="p-2 rounded-xl bg-[#E7FFDB] dark:bg-[#144D37]" dir="rtl">
            <div class="overflow-hidden bg-white rounded-lg dark:bg-ink-900">
                @if ($image)
                    <img src="{{ $image }}" alt="" class="object-cover w-full h-32">
                @else
                    <div class="flex items-center justify-center w-full h-32 bg-ink-100 dark:bg-ink-800">
                        <x-icon name="image" :size="24" class="text-ink-400" />
                    </div>
                @endif

                <div class="p-2.5">
                    <p class="text-[13px] font-bold leading-5 text-ink-900 dark:text-ink-100 line-clamp-2">{{ $title }}</p>
                    <p class="mt-0.5 text-[12px] leading-4 text-ink-500 dark:text-ink-400 line-clamp-2">{{ \Illuminate\Support\Str::limit($description, 90) }}</p>
                    <p class="mt-1 text-[11px] text-ink-400" dir="ltr">{{ $host }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- ═══ فيسبوك ولينكدإن ═══ --}}
    <div class="p-5 border rounded-2xl border-ink-200 bg-white dark:border-ink-800 dark:bg-ink-950">
        <div class="flex items-center gap-2 mb-4">
            <x-icon name="external-link" :size="15" class="text-ink-400" />
            <span class="text-xs font-bold tracking-wide text-ink-500 dark:text-ink-400">فيسبوك ولينكدإن</span>
        </div>

        <div class="overflow-hidden border rounded-lg border-ink-200 dark:border-ink-700" dir="rtl">
            @if ($image)
                <img src="{{ $image }}" alt="" class="object-cover w-full aspect-[1.91/1]">
            @else
                <div class="flex items-center justify-center w-full aspect-[1.91/1] bg-ink-100 dark:bg-ink-800">
                    <x-icon name="image" :size="28" class="text-ink-400" />
                </div>
            @endif

            <div class="p-3 bg-ink-50 dark:bg-ink-900">
                <p class="text-[11px] uppercase tracking-wide text-ink-500" dir="ltr">{{ $host }}</p>
                <p class="mt-1 text-sm font-bold leading-5 text-ink-900 dark:text-ink-100 line-clamp-2">{{ $title }}</p>
                <p class="mt-1 text-xs leading-5 text-ink-500 dark:text-ink-400 line-clamp-1">{{ \Illuminate\Support\Str::limit($description, 100) }}</p>
            </div>
        </div>
    </div>

    {{-- ═══ عدّادات الطول ═══ --}}
    <div class="p-5 border rounded-2xl border-ink-200 bg-white dark:border-ink-800 dark:bg-ink-950">
        <div class="flex items-center gap-2 mb-4">
            <x-icon name="check-circle" :size="15" class="text-ink-400" />
            <span class="text-xs font-bold tracking-wide text-ink-500 dark:text-ink-400">فحص الأطوال</span>
        </div>

        @foreach ([['العنوان', mb_strlen(trim($title)), 50, $titleLimit], ['الوصف', mb_strlen($description), 120, $descLimit]] as [$label, $len, $min, $max])
            @php
                $state = $len === 0 ? 'empty' : ($len > $max ? 'over' : ($len < $min ? 'short' : 'ok'));
                $pct = $max > 0 ? min(100, round($len / $max * 100)) : 0;
            @endphp

            <div class="mb-4 last:mb-0">
                <div class="flex items-baseline justify-between mb-1.5">
                    <span class="text-xs font-bold text-ink-700 dark:text-ink-300">{{ $label }}</span>
                    <span @class([
                        'text-xs font-bold tabular-nums',
                        'text-ink-400' => $state === 'empty',
                        'text-red-600 dark:text-red-400' => $state === 'over',
                        'text-amber-600 dark:text-amber-400' => $state === 'short',
                        'text-green-600 dark:text-green-400' => $state === 'ok',
                    ])>{{ $len }} / {{ $max }}</span>
                </div>

                <div class="h-1.5 overflow-hidden rounded-full bg-ink-150 dark:bg-ink-800">
                    <div @class([
                        'h-full rounded-full transition-all',
                        'bg-ink-300' => $state === 'empty',
                        'bg-red-500' => $state === 'over',
                        'bg-amber-500' => $state === 'short',
                        'bg-green-500' => $state === 'ok',
                    ]) style="width:{{ $pct }}%"></div>
                </div>

                <p class="mt-1.5 text-[11px] leading-4 text-ink-500 dark:text-ink-400">
                    @switch($state)
                        @case('empty') فارغ — سيُستعمل النص الافتراضي. @break
                        @case('over') أطول من اللازم؛ سيُقصّ في نتائج البحث. @break
                        @case('short') أقصر من المثالي ({{ $min }}–{{ $max }}). @break
                        @default ضمن المدى المثالي.
                    @endswitch
                </p>
            </div>
        @endforeach
    </div>
</div>
