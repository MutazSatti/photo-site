@props([
    'seed' => '',
    'icon' => null,
    'class' => '',
])

@php
    /*
       رسم مولَّد يملأ موضع الصورة الغائبة.

       ليس صورة فوتوغرافية ولا يحاول أن يبدو كذلك: على موقع مصوّر، صورة تبدو
       التقاطًا حقيقيًا وليست كذلك تخدع الزائر في أهمّ ما يقيّم به صاحب الموقع.
       فهو رسم هندسي صريح — دوائر بوكيه وحلقات بؤرة — يقول «هنا لا صورة» بلغة
       التصوير نفسها.

       والتوليد ثابت لا عشوائي: البذرة اسم العنصر، فيأخذ كل عنصر تركيبته الخاصة
       ويحتفظ بها في كل تصيير. لو كانت عشوائية لتبدّل الرسم مع كل تحديث للصفحة.
    */
    $hash = hash('sha256', (string) $seed);
    $byte = fn (int $i) => hexdec(substr($hash, ($i * 2) % 64, 2));
    $pick = fn (int $i, int $min, int $max) => $min + ($byte($i) % max(1, $max - $min + 1));

    // بؤرة التركيب — تُزاح عن المركز فلا تخرج كل الرسوم متطابقة
    $fx = $pick(0, 130, 285);
    $fy = $pick(1, 95, 205);

    // دوائر البوكيه موزّعة حول البؤرة لا في الفراغ، فتبدو ضوءًا خارج العمق لا نثارًا
    $bokeh = [];
    for ($i = 0; $i < 7; $i++) {
        $angle = deg2rad($pick(2 + $i, 0, 359));
        $dist = $pick(10 + $i, 60, 190);
        $bokeh[] = [
            'x' => round($fx + cos($angle) * $dist, 1),
            'y' => round($fy + sin($angle) * $dist * 0.62, 1),
            'r' => $pick(18 + $i, 9, 30),
            'o' => $pick(26 + $i, 7, 20) / 100,
        ];
    }
@endphp

<div {{ $attributes->class(['relative overflow-hidden bg-gradient-to-br from-ink-100 via-ink-100 to-brand-100 dark:from-ink-800 dark:via-ink-800 dark:to-ink-900', $class]) }} aria-hidden="true">
    <svg class="absolute inset-0 size-full" viewBox="0 0 400 300" preserveAspectRatio="xMidYMid slice" fill="none">
        {{-- هالة الضوء خلف كل شيء --}}
        <defs>
            <radialGradient id="glow-{{ substr($hash, 0, 8) }}" cx="{{ $fx / 4 }}%" cy="{{ $fy / 3 }}%" r="70%">
                <stop offset="0%" class="text-brand-300 dark:text-brand-900" stop-color="currentColor" stop-opacity="0.55" />
                <stop offset="100%" stop-color="currentColor" stop-opacity="0" />
            </radialGradient>
        </defs>
        <rect width="400" height="300" fill="url(#glow-{{ substr($hash, 0, 8) }})" />

        {{-- دوائر البوكيه: حوافّ فقط لا مصمتة، فتبقى خفيفة تحت الأيقونة --}}
        @foreach ($bokeh as $b)
            <circle
                cx="{{ $b['x'] }}" cy="{{ $b['y'] }}" r="{{ $b['r'] }}"
                stroke="currentColor" stroke-width="1.5"
                class="text-brand-400 dark:text-brand-700"
                opacity="{{ $b['o'] }}"
            />
        @endforeach

        {{-- حلقتا البؤرة حول مركز التركيب --}}
        <circle cx="{{ $fx }}" cy="{{ $fy }}" r="46" stroke="currentColor" stroke-width="1" class="text-ink-400 dark:text-ink-600" opacity="0.30" />
        <circle cx="{{ $fx }}" cy="{{ $fy }}" r="68" stroke="currentColor" stroke-width="1" class="text-ink-400 dark:text-ink-600" opacity="0.16" />
    </svg>

    @if ($icon)
        <div class="absolute inset-0 flex items-center justify-center">
            <x-icon :name="$icon" :size="48" :stroke="1.25" class="text-brand-400/70 dark:text-ink-600" />
        </div>
    @endif
</div>
