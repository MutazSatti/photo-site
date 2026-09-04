{{-- ================= الواجهة ================= --}}
<section data-block="hero" class="relative isolate overflow-hidden bg-ink-950">

    {{-- الصورة الرئيسية: أوّل ما يُرسم في الصفحة، فتُحمَّل بأولوية عالية --}}
    @if ($this->heroImage)
        {{--
            المقاسات هنا لا تتبع عرض الشاشة وحده: الواجهة صندوق طويل والصورة
            أفقية، فـ object-cover يقصّها أفقيًا بشدة على الجوال ولا يظهر منها
            إلا نحو ربع عرضها. لذلك يُطلب مقاس أكبر بكثير من عرض الشاشة على
            الشاشات الضيقة، وإلا خرجت الواجهة ضبابية. المقاس المصغّر مستبعد
            تمامًا لأنه لا يصلح لصورة تملأ الشاشة.
        --}}
        <img
            src="{{ $this->heroImage->url('lg') }}"
            srcset="{{ $this->heroImage->url('md') }} 800w, {{ $this->heroImage->url('lg') }} 1600w, {{ $this->heroImage->url('full') }} 2400w"
            sizes="(max-width: 640px) 400vw, (max-width: 1024px) 160vw, 100vw"
            alt="{{ $this->heroImage->altText() }}"
            width="{{ $this->heroImage->width }}"
            height="{{ $this->heroImage->height }}"
            fetchpriority="high"
            decoding="sync"
            class="absolute inset-0 object-cover size-full object-[50%_45%]"
        >
    @else
        <div class="absolute inset-0 bg-gradient-to-b from-ink-800 to-ink-950" aria-hidden="true"></div>
    @endif

    {{--
        طبقتان من التعتيم: واحدة عامة تضمن تباين النص فوق أي صورة،
        وأخرى تتدرّج من جهة النص (اليمين في RTL) لتُبقي وسط الصورة ظاهرًا.
    --}}
    <div class="absolute inset-0 bg-ink-950/45" aria-hidden="true"></div>
    <div class="absolute inset-0 bg-gradient-to-l from-ink-950 via-ink-950/70 to-transparent" aria-hidden="true"></div>
    <div class="absolute inset-x-0 bottom-0 h-40 bg-gradient-to-t from-ink-950 to-transparent" aria-hidden="true"></div>

    <div class="relative flex min-h-[70vh] items-center px-4 py-20 mx-auto max-w-7xl sm:min-h-[78vh] sm:px-6 sm:py-24 lg:min-h-[86vh] lg:px-8">
        <div class="max-w-2xl">
            <div class="inline-flex items-center gap-2 rounded-full border border-white/20 bg-white/10 px-3.5 py-1.5 text-xs font-bold text-white backdrop-blur-sm">
                <x-icon name="map-pin" :size="13" />
                {{ config('site.location.city') }} — {{ config('site.location.country_name') }}
            </div>

            <h1 class="mt-6 text-4xl font-extrabold leading-[1.15] text-balance text-white sm:text-5xl lg:text-6xl">
                {{ setting('hero_title') }}
            </h1>

            <p class="max-w-xl mt-6 text-base leading-9 text-ink-200 sm:text-lg">
                {{ setting('hero_subtitle') }}
            </p>

            <div class="flex flex-wrap gap-3 mt-9">
                <x-ui.button href="{{ route('contact') }}" variant="brand" size="lg" icon="camera">
                    {{ setting('hero_cta', 'احجز موعد تصوير') }}
                </x-ui.button>

                <x-ui.button
                    href="{{ route('portfolio') }}"
                    variant="outline"
                    size="lg"
                    icon-after="arrow-left"
                    class="text-white border-white/30 hover:bg-white/10"
                >
                    تصفّح المعرض
                </x-ui.button>
            </div>

            {{-- أرقام سريعة --}}
            <dl class="grid max-w-2xl grid-cols-2 gap-6 pt-8 mt-12 border-t border-white/15 sm:grid-cols-4">
                @foreach ([
                    ['value' => setting('about_years', 10), 'label' => 'سنوات خبرة', 'suffix' => '+'],
                    ['value' => setting('stat_projects', 450), 'label' => 'مشروع مصوَّر', 'suffix' => '+'],
                    ['value' => setting('stat_clients', 180), 'label' => 'عميل', 'suffix' => '+'],
                    ['value' => setting('stat_workshops', 35), 'label' => 'ورشة تدريبية', 'suffix' => '+'],
                ] as $stat)
                    <div>
                        <dt class="sr-only">{{ $stat['label'] }}</dt>
                        <dd class="text-3xl font-extrabold text-brand-400" dir="ltr">
                            {{ number_format((int) $stat['value']) }}{{ $stat['suffix'] }}
                        </dd>
                        <p class="mt-1 text-sm text-ink-300">{{ $stat['label'] }}</p>
                    </div>
                @endforeach
            </dl>
        </div>
    </div>
</section>

