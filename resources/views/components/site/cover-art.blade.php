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
       فهو رسم هندسي صريح يقول «هنا لا صورة» بلغة التصوير نفسها.

       ولكل تخصّص زخرفته: العقارات واجهات ونوافذ، والتصوير الجوي مراوح وحلقات
       ارتفاع، والمناسبات ثريّا وأضواء… فالترويسة تدلّ على محتواها قبل أن يُقرأ
       العنوان. والزخرفة تُشتقّ من أيقونة القسم لا من معامل جديد، فلا يحتاج أي
       استدعاء قائم تعديلًا، وأي قسم يُضاف بأيقونة معروفة يأخذ زخرفته تلقائيًا.

       والقاعدة المشتركة — الهالة والبوكيه — تبقى تحت كل زخرفة فتنتمي كلها إلى
       عائلة واحدة بدل أن تبدو رسومًا متفرّقة.
    */
    $motif = match ($icon) {
        'camera', 'aperture' => 'lens',
        'sparkles' => 'celebration',
        'presentation' => 'stage',
        'building' => 'facades',
        'drone' => 'aerial',
        'academic' => 'dial',
        'document' => 'columns',
        'lightbulb' => 'triangle',
        'images' => 'frames',
        'user' => 'portrait',
        'phone', 'send' => 'signal',
        'help' => 'bubbles',
        default => null,
    };

    // توليد ثابت من البذرة: نفس العنصر يعطي نفس الرسم في كل تصيير. لو كان
    // عشوائيًا لتبدّل مع كل تحديث للصفحة.
    $hash = hash('sha256', (string) $seed);
    $byte = fn (int $i) => hexdec(substr($hash, ($i * 2) % 64, 2));
    $pick = fn (int $i, int $min, int $max) => $min + ($byte($i) % max(1, $max - $min + 1));

    $fx = $pick(0, 150, 260);
    $fy = $pick(1, 110, 190);

    // البوكيه يلتفّ حول البؤرة لا ينثر في الفراغ، فيبدو ضوءًا خارج العمق
    $bokeh = [];
    for ($i = 0; $i < 6; $i++) {
        $angle = deg2rad($pick(2 + $i, 0, 359));
        $dist = $pick(10 + $i, 80, 195);
        $bokeh[] = [
            'x' => round($fx + cos($angle) * $dist, 1),
            'y' => round($fy + sin($angle) * $dist * 0.6, 1),
            'r' => $pick(18 + $i, 10, 28),
            'o' => $pick(26 + $i, 6, 16) / 100,
        ];
    }

    $id = substr($hash, 0, 8);

    // العناصر الجوهرية تبقى داخل هذا النطاق: الرسم يُقصّ بـslice في الأطر
    // العريضة (شريط المقال 4.4:1)، فما خرج عنه يضيع.
    $gold = 'text-brand-500 dark:text-brand-600';
    $ink = 'text-ink-400 dark:text-ink-600';
@endphp

<div {{ $attributes->class(['relative overflow-hidden bg-gradient-to-br from-ink-100 via-ink-100 to-brand-100 dark:from-ink-800 dark:via-ink-800 dark:to-ink-900', $class]) }} aria-hidden="true">
    <svg class="absolute inset-0 size-full" viewBox="0 0 400 300" preserveAspectRatio="xMidYMid slice" fill="none"
        stroke-linecap="round" stroke-linejoin="round">

        <defs>
            <radialGradient id="glow-{{ $id }}" cx="{{ round($fx / 4) }}%" cy="{{ round($fy / 3) }}%" r="70%">
                <stop offset="0%" class="text-brand-300 dark:text-brand-900" stop-color="currentColor" stop-opacity="0.5" />
                <stop offset="100%" stop-color="currentColor" stop-opacity="0" />
            </radialGradient>
        </defs>
        <rect width="400" height="300" fill="url(#glow-{{ $id }})" />

        @foreach ($bokeh as $b)
            <circle cx="{{ $b['x'] }}" cy="{{ $b['y'] }}" r="{{ $b['r'] }}"
                stroke="currentColor" stroke-width="1.5" class="{{ $gold }}" opacity="{{ $b['o'] }}" />
        @endforeach

        @switch ($motif)
            @case ('lens')
                {{-- عدسة: براميل متراكزة وشفرات حدقة --}}
                <g stroke="currentColor" class="{{ $gold }}" opacity="0.42" stroke-width="1.5">
                    <circle cx="200" cy="150" r="72" />
                    <circle cx="200" cy="150" r="52" />
                    <polygon points="200,108 236,129 236,171 200,192 164,171 164,129" opacity="0.75" />
                    <circle cx="200" cy="150" r="14" />
                </g>
                <g stroke="currentColor" class="{{ $ink }}" opacity="0.22" stroke-width="1.25">
                    <circle cx="200" cy="150" r="92" stroke-dasharray="3 9" />
                </g>
                @break

            @case ('celebration')
                {{-- ثريّا القاعة: قوس معلّق تتدلّى منه أضواء متفاوتة --}}
                <g stroke="currentColor" class="{{ $ink }}" opacity="0.25" stroke-width="1.25">
                    <path d="M110 74h180" />
                    <path d="M200 74v20" />
                    <path d="M126 96a74 74 0 0 0 148 0" />
                </g>
                <g class="{{ $gold }}" opacity="0.45">
                    @foreach ([[140, 128, 9], [170, 148, 13], [200, 158, 16], [230, 148, 13], [260, 128, 9]] as [$cx, $cy, $r])
                        <line x1="{{ $cx }}" y1="96" x2="{{ $cx }}" y2="{{ $cy - $r }}" stroke="currentColor" stroke-width="1.25" opacity="0.6" />
                        <circle cx="{{ $cx }}" cy="{{ $cy }}" r="{{ $r }}" stroke="currentColor" stroke-width="1.5" />
                    @endforeach
                </g>
                <g stroke="currentColor" class="{{ $gold }}" opacity="0.35" stroke-width="1.5">
                    <path d="M148 210l6-14 6 14 14 6-14 6-6 14-6-14-14-6z" />
                    <path d="M246 196l4-10 4 10 10 4-10 4-4 10-4-10-10-4z" />
                </g>
                @break

            @case ('stage')
                {{-- منصّة: شاشة ومخروط ضوء وصفوف حضور --}}
                <g stroke="currentColor" class="{{ $ink }}" opacity="0.3" stroke-width="1.5">
                    <rect x="132" y="82" width="136" height="82" rx="6" />
                    <path d="M200 164v22" />
                    <path d="M176 186h48" />
                </g>
                <path d="M200 66l58 98H142z" stroke="currentColor" class="{{ $gold }}" opacity="0.22" stroke-width="1.25" />
                <g class="{{ $gold }}" opacity="0.42">
                    @for ($row = 0; $row < 3; $row++)
                        @for ($col = 0; $col < 7; $col++)
                            <circle cx="{{ 128 + $col * 24 + ($row % 2) * 12 }}" cy="{{ 214 + $row * 22 }}" r="5"
                                stroke="currentColor" stroke-width="1.5" />
                        @endfor
                    @endfor
                </g>
                @break

            @case ('facades')
                {{-- واجهات: كتل بارتفاعات مختلفة بشبكات نوافذ --}}
                <g stroke="currentColor" class="{{ $ink }}" opacity="0.3" stroke-width="1.5">
                    <rect x="112" y="140" width="70" height="112" rx="4" />
                    <rect x="190" y="96" width="78" height="156" rx="4" />
                    <rect x="276" y="164" width="62" height="88" rx="4" />
                    <path d="M96 252h224" />
                </g>
                <g class="{{ $gold }}" opacity="0.4">
                    @foreach ([[112, 140, 70, 112], [190, 96, 78, 156], [276, 164, 62, 88]] as [$bx, $by, $bw, $bh])
                        @for ($r = 0; $r < intdiv($bh - 20, 28); $r++)
                            @for ($c = 0; $c < intdiv($bw - 16, 24); $c++)
                                <rect x="{{ $bx + 14 + $c * 24 }}" y="{{ $by + 16 + $r * 28 }}" width="13" height="16" rx="1.5"
                                    stroke="currentColor" stroke-width="1.25" />
                            @endfor
                        @endfor
                    @endforeach
                </g>
                @break

            @case ('aerial')
                {{-- منظور علوي: مراوح على إطار متصالب وحلقات ارتفاع فوق مخطّط أرض --}}
                <g stroke="currentColor" class="{{ $ink }}" opacity="0.18" stroke-width="1.25">
                    @for ($g = 0; $g < 5; $g++)
                        <path d="M{{ 96 + $g * 52 }} 60v192" />
                        <path d="M96 {{ 84 + $g * 42 }}h208" />
                    @endfor
                </g>
                <g stroke="currentColor" class="{{ $gold }}" opacity="0.45" stroke-width="1.5">
                    <path d="M162 112l76 76M238 112l-76 76" />
                    @foreach ([[162, 112], [238, 112], [162, 188], [238, 188]] as [$cx, $cy])
                        <circle cx="{{ $cx }}" cy="{{ $cy }}" r="22" opacity="0.55" />
                        <circle cx="{{ $cx }}" cy="{{ $cy }}" r="4" />
                    @endforeach
                    <rect x="186" y="136" width="28" height="28" rx="6" />
                </g>
                @break

            @case ('dial')
                {{-- قرص الكاميرا: حلقة بعلامات فتحات ومؤشّر --}}
                <g stroke="currentColor" class="{{ $gold }}" opacity="0.42" stroke-width="1.5">
                    <circle cx="200" cy="152" r="76" />
                    <circle cx="200" cy="152" r="20" />
                    <path d="M200 132V96" stroke-width="2" />
                </g>
                <g stroke="currentColor" class="{{ $ink }}" opacity="0.32" stroke-width="1.5">
                    @for ($t = 0; $t < 12; $t++)
                        @php($a = deg2rad($t * 30 - 90))
                        <line
                            x1="{{ round(200 + cos($a) * 86, 1) }}" y1="{{ round(152 + sin($a) * 86, 1) }}"
                            x2="{{ round(200 + cos($a) * ($t % 3 === 0 ? 98 : 93), 1) }}" y2="{{ round(152 + sin($a) * ($t % 3 === 0 ? 98 : 93), 1) }}"
                        />
                    @endfor
                </g>
                @break

            @case ('columns')
                {{-- أعمدة قراءة: سطور متفاوتة وعلامة اقتباس --}}
                <g stroke="currentColor" class="{{ $ink }}" opacity="0.3" stroke-width="4">
                    @foreach ([200, 168, 190, 146, 178, 120] as $i => $w)
                        <path d="M{{ 268 - $w }} {{ 112 + $i * 24 }}h{{ $w }}" opacity="{{ 1 - $i * 0.11 }}" />
                    @endforeach
                </g>
                <g stroke="currentColor" class="{{ $gold }}" opacity="0.4" stroke-width="1.5">
                    <path d="M120 92c-14 0-24 10-24 24s10 22 22 22c-2 14-10 22-22 26" />
                    <path d="M296 236h-8" />
                </g>
                @break

            @case ('triangle')
                {{-- مثلث التعريض: ثلاثة رؤوس وأشعّة --}}
                <g stroke="currentColor" class="{{ $gold }}" opacity="0.45" stroke-width="1.5">
                    <path d="M200 88l68 118H132z" />
                    @foreach ([[200, 88], [268, 206], [132, 206]] as [$cx, $cy])
                        <circle cx="{{ $cx }}" cy="{{ $cy }}" r="11" />
                    @endforeach
                </g>
                <g stroke="currentColor" class="{{ $ink }}" opacity="0.26" stroke-width="1.5">
                    @for ($r = 0; $r < 8; $r++)
                        @php($a = deg2rad($r * 45))
                        <line
                            x1="{{ round(200 + cos($a) * 96, 1) }}" y1="{{ round(150 + sin($a) * 96, 1) }}"
                            x2="{{ round(200 + cos($a) * 116, 1) }}" y2="{{ round(150 + sin($a) * 116, 1) }}"
                        />
                    @endfor
                </g>
                @break

            @case ('frames')
                {{-- شبكة إطارات بأحجام مختلفة --}}
                <g stroke="currentColor" class="{{ $gold }}" opacity="0.42" stroke-width="1.5">
                    <rect x="104" y="96" width="86" height="66" rx="6" />
                    <rect x="200" y="96" width="60" height="104" rx="6" />
                    <rect x="270" y="96" width="66" height="66" rx="6" />
                    <rect x="104" y="172" width="86" height="80" rx="6" />
                    <rect x="200" y="210" width="60" height="42" rx="6" />
                    <rect x="270" y="172" width="66" height="80" rx="6" />
                </g>
                <g stroke="currentColor" class="{{ $ink }}" opacity="0.28" stroke-width="1.5">
                    <path d="M118 148l18-18 16 16 12-10 16 16" />
                    <circle cx="152" cy="116" r="5" />
                </g>
                @break

            @case ('portrait')
                {{-- إطار بورتريه بأقواس تركيز --}}
                <g stroke="currentColor" class="{{ $ink }}" opacity="0.3" stroke-width="1.5">
                    <circle cx="200" cy="140" r="34" />
                    <path d="M148 226a52 52 0 0 1 104 0" />
                </g>
                <g stroke="currentColor" class="{{ $gold }}" opacity="0.45" stroke-width="2">
                    <path d="M120 108V88h20M280 108V88h-20M120 208v20h20M280 208v20h-20" />
                </g>
                @break

            @case ('signal')
                {{-- دبوس موقع وموجات وفقاعة رسالة --}}
                <g stroke="currentColor" class="{{ $gold }}" opacity="0.45" stroke-width="1.5">
                    <path d="M200 214c0 0 34-38 34-62a34 34 0 1 0-68 0c0 24 34 62 34 62z" />
                    <circle cx="200" cy="152" r="12" />
                </g>
                <g stroke="currentColor" class="{{ $ink }}" opacity="0.22" stroke-width="1.5">
                    <path d="M140 236a86 46 0 0 0 120 0" />
                    <path d="M116 252a120 60 0 0 0 168 0" />
                </g>
                <g stroke="currentColor" class="{{ $gold }}" opacity="0.3" stroke-width="1.5">
                    <path d="M268 92h56a8 8 0 0 1 8 8v28a8 8 0 0 1-8 8h-34l-14 12v-12h-8a8 8 0 0 1-8-8v-28a8 8 0 0 1 8-8z" />
                </g>
                @break

            @case ('bubbles')
                {{-- فقاعتا حوار ونقاط سؤال --}}
                <g stroke="currentColor" class="{{ $gold }}" opacity="0.45" stroke-width="1.5">
                    <path d="M116 96h116a10 10 0 0 1 10 10v52a10 10 0 0 1-10 10h-74l-22 18v-18h-20a10 10 0 0 1-10-10v-52a10 10 0 0 1 10-10z" />
                </g>
                <g stroke="currentColor" class="{{ $ink }}" opacity="0.3" stroke-width="1.5">
                    <path d="M196 174h92a10 10 0 0 1 10 10v46a10 10 0 0 1-10 10h-56l-20 16v-16h-16a10 10 0 0 1-10-10v-46a10 10 0 0 1 10-10z" />
                </g>
                <g stroke="currentColor" class="{{ $gold }}" opacity="0.4" stroke-width="2">
                    <path d="M162 118a12 12 0 1 1 12 12v8" />
                    <path d="M174 148v2" />
                </g>
                @break
        @endswitch

        {{-- حلقتا البؤرة تربطان كل الزخارف بأصل واحد --}}
        <circle cx="{{ $fx }}" cy="{{ $fy }}" r="48" stroke="currentColor" stroke-width="1" class="{{ $ink }}" opacity="0.18" />
        <circle cx="{{ $fx }}" cy="{{ $fy }}" r="72" stroke="currentColor" stroke-width="1" class="{{ $ink }}" opacity="0.10" />
    </svg>

    {{-- الأيقونة تُعرض حين لا زخرفة لهذا التخصّص، فلا تزدحم مع رسمها --}}
    @if ($icon && ! $motif)
        <div class="absolute inset-0 flex items-center justify-center">
            <x-icon :name="$icon" :size="48" :stroke="1.25" class="text-brand-400/70 dark:text-ink-600" />
        </div>
    @endif
</div>
