@props([
    'title',
    'tagline' => null,
    'description' => null,
    'icon' => null,
    'breadcrumbs' => [],
    'photo' => null,
])

{{--
    ترويستان لا واحدة.

    حين تتوفّر للصفحة صورة حقيقية من أعمال المصوّر تأخذ الترويسة كلها: صورة
    ملء العرض فوق أرضية داكنة ونصّ أبيض. هذا موقع مصوّر، وأصدق ما يقوله عن
    نفسه في أول شاشة صورةٌ التقطها فعلًا.

    وحين لا تتوفّر تبقى الترويسة الفاتحة برسمها المولَّد — بديل مؤقّت لا
    يدّعي أنه صورة، ريثما تصل صورة تخصّ الصفحة.
--}}

@if ($photo)
    <header class="relative overflow-hidden bg-ink-950">
        {{--
            زينة لا محتوى: العنوان تحتها يقول ما تقوله، فتركُها بلا نصّ بديل
            يوفّر على قارئ الشاشة تكرارًا لا يفيده.
        --}}
        <img
            src="{{ $photo }}"
            alt=""
            aria-hidden="true"
            loading="eager"
            decoding="sync"
            fetchpriority="high"
            class="absolute inset-0 object-cover size-full opacity-65"
        >

        {{--
            حجابان: رأسيّ يبني أرضية داكنة تحت النص، وأفقيّ يعتّم جهة بدايته
            وحدها فيبقى الطرف الآخر صورةً مرئية لا ظلامًا.

            اتجاه التدرّج فيزيائي لا منطقي، والموقع RTL ثابتًا، فـ to-l يجعل
            from عند اليمين حيث يبدأ النص العربي.
        --}}
        <div class="absolute inset-0 bg-gradient-to-t from-ink-950 via-ink-950/70 to-ink-950/25" aria-hidden="true"></div>
        <div class="absolute inset-0 bg-gradient-to-l from-ink-950/60 to-transparent" aria-hidden="true"></div>

        <div class="relative px-4 py-16 mx-auto max-w-7xl sm:px-6 lg:px-8 lg:py-24">
            @if ($breadcrumbs !== [])
                <x-site.breadcrumbs
                    :items="$breadcrumbs"
                    class="mb-6 [&_ol]:text-white/60 [&_a:hover]:text-white [&_[aria-current]]:text-white"
                />
            @endif

            <div class="max-w-3xl">
                @if ($icon)
                    <span class="mb-5 inline-flex size-12 items-center justify-center rounded-2xl bg-brand-500 text-ink-950">
                        <x-icon :name="$icon" :size="24" :stroke="2" />
                    </span>
                @endif

                @if ($tagline)
                    <p class="mb-2 text-sm font-bold text-brand-400">{{ $tagline }}</p>
                @endif

                <h1 class="text-3xl font-extrabold leading-tight text-white text-balance sm:text-4xl lg:text-5xl">
                    {{ $title }}
                </h1>

                @if ($description)
                    <p class="mt-5 text-base leading-8 text-white/80 sm:text-lg">
                        {{ $description }}
                    </p>
                @endif

                @if (isset($actions))
                    <div class="flex flex-wrap gap-3 mt-8">
                        {{ $actions }}
                    </div>
                @endif
            </div>
        </div>
    </header>
@else
    <header class="relative overflow-hidden border-b border-ink-200 bg-ink-50 dark:border-ink-800 dark:bg-ink-900">
        {{-- نقش خفيف يمنع الخلفية من أن تبدو مسطّحة تمامًا --}}
        <div class="absolute inset-0 opacity-40 dark:opacity-20" aria-hidden="true">
            <svg class="size-full" xmlns="http://www.w3.org/2000/svg">
                <defs>
                    <pattern id="ph-grid" width="32" height="32" patternUnits="userSpaceOnUse">
                        <path d="M0 32V0h32" fill="none" stroke="currentColor" stroke-width="0.5" class="text-ink-300 dark:text-ink-700" />
                    </pattern>
                </defs>
                <rect width="100%" height="100%" fill="url(#ph-grid)" />
            </svg>
        </div>

        {{--
            النصّ يشغل ثلاثة أخماس العرض، فيبقى خمساه فراغًا مسطّحًا على الشاشات
            الواسعة. رسم البؤرة يملؤه ويذوب في الحافّة فلا ينافس العنوان.

            يظهر من lg فقط: على الشاشات الضيقة لا فراغ أصلًا، وإقحامه هناك يزاحم
            النصّ الذي جاء الزائر لأجله.
        --}}
        {{--
            end لا start: الصفحة RTL فالنصّ يبدأ من اليمين والفراغ يقع يساره. ووضعه
            على start يضعه تحت العنوان نفسه.

            واتجاه التدرّج فيزيائي لا منطقي — والموقع RTL ثابتًا — فهو شفاف عند
            الحافّة اليسرى حيث يظهر الرسم، ومصمت عند اليمين حيث يبدأ النصّ.
        --}}
        <div class="absolute inset-y-0 hidden pointer-events-none end-0 w-1/3 lg:block" aria-hidden="true">
            <x-site.cover-art :seed="$title" :icon="$icon" class="size-full bg-transparent! dark:bg-transparent!" />
            <div class="absolute inset-0 bg-gradient-to-r from-transparent to-ink-50 dark:to-ink-900"></div>
        </div>

        <div class="relative px-4 py-12 mx-auto max-w-7xl sm:px-6 lg:px-8 lg:py-16">
            @if ($breadcrumbs !== [])
                <x-site.breadcrumbs :items="$breadcrumbs" class="mb-6" />
            @endif

            <div class="max-w-3xl">
                @if ($icon)
                    <span class="mb-5 inline-flex size-12 items-center justify-center rounded-2xl bg-brand-500 text-ink-950">
                        <x-icon :name="$icon" :size="24" :stroke="2" />
                    </span>
                @endif

                @if ($tagline)
                    <p class="mb-2 text-sm font-bold text-brand-600 dark:text-brand-400">{{ $tagline }}</p>
                @endif

                <h1 class="text-3xl font-extrabold leading-tight text-balance text-ink-900 sm:text-4xl lg:text-5xl dark:text-ink-50">
                    {{ $title }}
                </h1>

                @if ($description)
                    <p class="mt-5 text-base leading-8 text-ink-600 sm:text-lg dark:text-ink-400">
                        {{ $description }}
                    </p>
                @endif

                @if (isset($actions))
                    <div class="flex flex-wrap gap-3 mt-8">
                        {{ $actions }}
                    </div>
                @endif
            </div>
        </div>
    </header>
@endif
