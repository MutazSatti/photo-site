@php
    $brandColor = trim((string) \App\Models\Setting::get('brand_color'));

    // نتحقق من الصيغة هنا لا في العرض: القيمة تُحقن داخل CSS، وأي نص
    // غير مطابق يُهمَل بالكامل بدل تسريبه إلى الصفحة.
    $valid = $brandColor !== '' && preg_match('/^#[0-9a-fA-F]{6}$/', $brandColor);
@endphp

@if ($valid)
    {{--
        تجاوز تدرّج لون العلامة. اللون المختار هو الدرجة 500، وبقية
        الدرجات تُشتق منه بالمزج في فضاء oklab — يحفظ التدرّج البصري
        متوازنًا مع أي لون، بخلاف المزج في sRGB الذي يبهت في الأطراف.
        عند تركه فارغًا يبقى التدرّج الأصلي في app.css كما هو.
    --}}
    <style>
        :root {
            --brand-base: {{ $brandColor }};
            --color-brand-50: color-mix(in oklab, var(--brand-base), white 95%);
            --color-brand-100: color-mix(in oklab, var(--brand-base), white 88%);
            --color-brand-200: color-mix(in oklab, var(--brand-base), white 72%);
            --color-brand-300: color-mix(in oklab, var(--brand-base), white 50%);
            --color-brand-400: color-mix(in oklab, var(--brand-base), white 25%);
            --color-brand-500: var(--brand-base);
            --color-brand-600: color-mix(in oklab, var(--brand-base), black 15%);
            --color-brand-700: color-mix(in oklab, var(--brand-base), black 32%);
            --color-brand-800: color-mix(in oklab, var(--brand-base), black 48%);
            --color-brand-900: color-mix(in oklab, var(--brand-base), black 60%);
            --color-brand-950: color-mix(in oklab, var(--brand-base), black 78%);
        }
    </style>
@endif
