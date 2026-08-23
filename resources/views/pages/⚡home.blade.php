<?php

use App\Models\Category;
use App\Models\Faq;
use App\Models\Media;
use App\Models\Post;
use App\Models\Section;
use App\Models\Testimonial;
use App\Support\Schema;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    public function mount(): void
    {
        $city = config('site.location.city');
        $owner = config('site.owner_name');

        seo()
            ->set(
                title: setting('seo_title', "{$owner} | مصور فوتوغرافي محترف في {$city}"),
                description: setting('seo_description'),
                image: $this->heroImage?->url('lg'),
                type: 'website',
            )
            ->addGraph(
                [
                    '@type' => 'WebPage',
                    '@id' => route('home').'#page',
                    'url' => route('home'),
                    'name' => setting('seo_title'),
                    'description' => setting('seo_description'),
                    'inLanguage' => 'ar',
                    'isPartOf' => ['@id' => Schema::websiteId()],
                    'about' => ['@id' => Schema::businessId()],
                    'primaryImageOfPage' => ['@id' => Schema::personId()],
                ],
                Schema::faqPage($this->faqs, route('home')) ?? [],
            );
    }

    /** صورة واجهة الصفحة الرئيسية — تُرفع من إعدادات لوحة التحكم. */
    #[Computed]
    public function heroImage(): ?Media
    {
        return Media::where('usage', 'hero')->first();
    }

    #[Computed]
    public function sections()
    {
        return Section::query()->active()->ordered()->with('activeCategories')->get();
    }

    #[Computed]
    public function serviceCategories()
    {
        return Category::query()
            ->active()
            ->ordered()
            ->whereHas('section', fn ($q) => $q->where('slug', Section::SERVICES))
            ->withCount(['posts' => fn ($q) => $q->published()])
            ->get();
    }

    #[Computed]
    public function featured()
    {
        return Post::query()
            ->published()
            ->featured()
            ->ordered()
            ->with(['section:id,slug,name', 'category:id,slug,name,section_id', 'media'])
            ->take(6)
            ->get();
    }

    #[Computed]
    public function latestWorks()
    {
        return Post::query()
            ->published()
            ->inSection(Section::SERVICES)
            ->ordered()
            ->with(['section:id,slug,name', 'category:id,slug,name,section_id', 'media'])
            ->take(6)
            ->get();
    }

    #[Computed]
    public function latestReading()
    {
        return Post::query()
            ->published()
            ->whereHas('section', fn ($q) => $q->whereIn('slug', [Section::ARTICLES, Section::TIPS]))
            ->ordered()
            ->with(['section:id,slug,name', 'media'])
            ->take(4)
            ->get();
    }

    #[Computed]
    public function workshops()
    {
        return Post::query()
            ->published()
            ->inSection(Section::WORKSHOPS)
            ->ordered()
            ->with(['section:id,slug,name', 'media'])
            ->take(3)
            ->get();
    }

    #[Computed]
    public function testimonials()
    {
        return Testimonial::query()->active()->ordered()->take(3)->get();
    }

    #[Computed]
    public function faqs()
    {
        return Faq::query()->active()->ordered()->take(6)->get();
    }
}; ?>

<div>
    {{-- ================= الواجهة ================= --}}
    <section class="relative isolate overflow-hidden bg-ink-950">

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

    {{-- ================= الأقسام الرئيسية ================= --}}
    <section class="px-4 py-16 mx-auto max-w-7xl sm:px-6 lg:px-8 lg:py-20">
        <div class="max-w-2xl">
            <h2 class="text-2xl font-extrabold text-ink-900 sm:text-3xl dark:text-ink-50">أقسام المعرض</h2>
            <p class="mt-3 text-base leading-8 text-ink-600 dark:text-ink-400">
                أربعة أقسام تجمع العمل الميداني والتدريب والكتابة — كل قسم يحمل صوره وتفاصيله.
            </p>
        </div>

        <div class="grid gap-5 mt-10 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($this->sections as $section)
                <a
                    href="{{ $section->url() }}"
                    wire:navigate
                    class="flex flex-col p-6 transition-all border group rounded-2xl border-ink-200 bg-white hover:border-brand-400 hover:shadow-lg hover:shadow-black/5 dark:border-ink-800 dark:bg-ink-900 dark:hover:border-brand-600"
                >
                    <span class="flex items-center justify-center transition-colors size-12 rounded-2xl bg-ink-100 text-ink-700 group-hover:bg-brand-500 group-hover:text-ink-950 dark:bg-ink-800 dark:text-ink-300">
                        <x-icon :name="$section->icon" :size="24" :stroke="1.75" />
                    </span>

                    <h3 class="mt-5 text-lg font-extrabold text-ink-900 dark:text-ink-50">{{ $section->name }}</h3>
                    <p class="mt-1.5 text-sm font-bold text-brand-600 dark:text-brand-400">{{ $section->tagline }}</p>

                    <p class="mt-3 text-sm leading-7 text-ink-600 line-clamp-3 grow dark:text-ink-400">
                        {{ $section->description }}
                    </p>

                    <span class="mt-5 inline-flex items-center gap-1.5 text-sm font-bold text-ink-900 dark:text-ink-100">
                        استعرض القسم
                        <x-icon name="arrow-left" :size="15" class="transition-transform group-hover:-translate-x-1" />
                    </span>
                </a>
            @endforeach
        </div>
    </section>

    {{-- ================= خدمات التصوير ================= --}}
    <section class="border-y border-ink-200 bg-ink-50 dark:border-ink-800 dark:bg-ink-900">
        <div class="px-4 py-16 mx-auto max-w-7xl sm:px-6 lg:px-8 lg:py-20">
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div class="max-w-2xl">
                    <h2 class="text-2xl font-extrabold text-ink-900 sm:text-3xl dark:text-ink-50">
                        خدمات التصوير في {{ config('site.location.city') }}
                    </h2>
                    <p class="mt-3 text-base leading-8 text-ink-600 dark:text-ink-400">
                        أربعة تخصصات، لكل منها متطلباته وأسلوبه في التغطية والتسليم.
                    </p>
                </div>

                <x-ui.button href="{{ route('section.show', App\Models\Section::SERVICES) }}" variant="outline" icon-after="arrow-left">
                    كل الخدمات
                </x-ui.button>
            </div>

            <div class="grid gap-5 mt-10 sm:grid-cols-2">
                @foreach ($this->serviceCategories as $category)
                    <a
                        href="{{ $category->url() }}"
                        wire:navigate
                        class="flex items-start gap-5 p-6 transition-all bg-white border group rounded-2xl border-ink-200 hover:border-brand-400 hover:shadow-lg hover:shadow-black/5 dark:border-ink-800 dark:bg-ink-950 dark:hover:border-brand-600"
                    >
                        <span class="flex items-center justify-center transition-colors shrink-0 size-12 rounded-2xl bg-brand-50 text-brand-600 group-hover:bg-brand-500 group-hover:text-ink-950 dark:bg-brand-950 dark:text-brand-400">
                            <x-icon :name="$category->icon" :size="22" />
                        </span>

                        <div class="min-w-0">
                            <div class="flex items-center gap-2">
                                <h3 class="text-base font-extrabold text-ink-900 dark:text-ink-50">{{ $category->name }}</h3>
                                @if ($category->posts_count)
                                    <x-ui.badge>{{ $category->posts_count }}</x-ui.badge>
                                @endif
                            </div>

                            <p class="mt-1 text-sm font-bold text-brand-600 dark:text-brand-400">{{ $category->tagline }}</p>
                            <p class="mt-2 text-sm leading-7 text-ink-600 line-clamp-2 dark:text-ink-400">{{ $category->description }}</p>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ================= أعمال مختارة ================= --}}
    @php $works = $this->featured->isNotEmpty() ? $this->featured : $this->latestWorks; @endphp

    @if ($works->isNotEmpty())
        <section class="px-4 py-16 mx-auto max-w-7xl sm:px-6 lg:px-8 lg:py-20">
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div class="max-w-2xl">
                    <h2 class="text-2xl font-extrabold text-ink-900 sm:text-3xl dark:text-ink-50">أعمال مختارة</h2>
                    <p class="mt-3 text-base leading-8 text-ink-600 dark:text-ink-400">
                        نماذج من التغطيات الأخيرة — مناسبات ومؤتمرات وعقارات وبرامج تدريبية.
                    </p>
                </div>

                <x-ui.button href="{{ route('portfolio') }}" variant="outline" icon-after="arrow-left">
                    المعرض الكامل
                </x-ui.button>
            </div>

            <div class="grid gap-8 mt-10 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($works as $post)
                    <x-site.post-card :post="$post" :eager="$loop->index < 3" />
                @endforeach
            </div>
        </section>
    @endif

    {{-- ================= الورش التدريبية ================= --}}
    @if ($this->workshops->isNotEmpty())
        <section class="border-y border-ink-200 bg-ink-50 dark:border-ink-800 dark:bg-ink-900">
            <div class="px-4 py-16 mx-auto max-w-7xl sm:px-6 lg:px-8 lg:py-20">
                <div class="flex flex-wrap items-end justify-between gap-4">
                    <div class="max-w-2xl">
                        <h2 class="text-2xl font-extrabold text-ink-900 sm:text-3xl dark:text-ink-50">ورش تدريبية</h2>
                        <p class="mt-3 text-base leading-8 text-ink-600 dark:text-ink-400">
                            تعلّم التصوير بشكل عملي — مقاعد محدودة لضمان المتابعة الفردية.
                        </p>
                    </div>

                    <x-ui.button href="{{ route('section.show', App\Models\Section::WORKSHOPS) }}" variant="outline" icon-after="arrow-left">
                        كل الورش
                    </x-ui.button>
                </div>

                <div class="grid gap-6 mt-10 lg:grid-cols-3">
                    @foreach ($this->workshops as $workshop)
                        <a
                            href="{{ $workshop->url() }}"
                            wire:navigate
                            class="flex flex-col p-6 transition-all bg-white border group rounded-2xl border-ink-200 hover:border-brand-400 hover:shadow-lg hover:shadow-black/5 dark:border-ink-800 dark:bg-ink-950 dark:hover:border-brand-600"
                        >
                            <div class="flex flex-wrap gap-2">
                                @if ($workshop->duration)
                                    <x-ui.badge icon="clock">{{ $workshop->duration }}</x-ui.badge>
                                @endif
                                @if ($workshop->seats)
                                    <x-ui.badge icon="users">{{ $workshop->seats }} مقعد</x-ui.badge>
                                @endif
                            </div>

                            <h3 class="mt-4 text-lg font-extrabold leading-8 transition-colors text-ink-900 group-hover:text-brand-600 dark:text-ink-50 dark:group-hover:text-brand-400">
                                {{ $workshop->title }}
                            </h3>

                            <p class="mt-2 text-sm leading-7 text-ink-600 line-clamp-3 grow dark:text-ink-400">
                                {{ $workshop->excerpt }}
                            </p>

                            @if ($workshop->price)
                                <p class="pt-4 mt-5 text-lg font-extrabold border-t text-brand-600 border-ink-200 dark:border-ink-800 dark:text-brand-400">
                                    @money($workshop->price)
                                </p>
                            @endif
                        </a>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- ================= مقالات ومنشورات ================= --}}
    @if ($this->latestReading->isNotEmpty())
        <section class="px-4 py-16 mx-auto max-w-7xl sm:px-6 lg:px-8 lg:py-20">
            <div class="max-w-2xl">
                <h2 class="text-2xl font-extrabold text-ink-900 sm:text-3xl dark:text-ink-50">اقرأ وتعلّم</h2>
                <p class="mt-3 text-base leading-8 text-ink-600 dark:text-ink-400">
                    مقالات معمّقة ومنشورات تعليمية قصيرة — خلاصة تجربة ميدانية.
                </p>
            </div>

            <div class="grid gap-5 mt-10 sm:grid-cols-2">
                @foreach ($this->latestReading as $item)
                    <a
                        href="{{ $item->url() }}"
                        wire:navigate
                        class="flex flex-col p-6 transition-all border group rounded-2xl border-ink-200 hover:border-brand-400 hover:bg-ink-50 dark:border-ink-800 dark:hover:border-brand-600 dark:hover:bg-ink-900"
                    >
                        <div class="flex items-center gap-2 text-xs text-ink-500 dark:text-ink-400">
                            <x-ui.badge variant="brand">{{ $item->section->name }}</x-ui.badge>
                            <span class="inline-flex items-center gap-1">
                                <x-icon name="clock" :size="12" />
                                {{ $item->readingTime() }} دقائق قراءة
                            </span>
                        </div>

                        <h3 class="mt-4 text-lg font-extrabold leading-8 transition-colors text-ink-900 group-hover:text-brand-600 dark:text-ink-50 dark:group-hover:text-brand-400">
                            {{ $item->title }}
                        </h3>

                        <p class="mt-2 text-sm leading-7 text-ink-600 line-clamp-2 dark:text-ink-400">
                            {{ $item->excerpt }}
                        </p>
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    {{-- ================= آراء العملاء ================= --}}
    @if ($this->testimonials->isNotEmpty())
        <section class="border-y border-ink-200 bg-ink-50 dark:border-ink-800 dark:bg-ink-900">
            <div class="px-4 py-16 mx-auto max-w-7xl sm:px-6 lg:px-8">
                <h2 class="text-2xl font-extrabold text-ink-900 sm:text-3xl dark:text-ink-50">آراء العملاء</h2>

                <div class="grid gap-5 mt-10 lg:grid-cols-3">
                    @foreach ($this->testimonials as $testimonial)
                        <figure class="flex flex-col p-6 bg-white border rounded-2xl border-ink-200 dark:border-ink-800 dark:bg-ink-950">
                            <div class="flex gap-0.5 text-brand-500" role="img" aria-label="تقييم {{ $testimonial->rating }} من 5">
                                @for ($i = 0; $i < $testimonial->rating; $i++)
                                    <x-icon name="star-filled" :size="15" />
                                @endfor
                            </div>

                            <blockquote class="mt-4 text-sm leading-8 text-ink-700 grow dark:text-ink-300">
                                {{ $testimonial->content }}
                            </blockquote>

                            <figcaption class="pt-4 mt-5 border-t border-ink-200 dark:border-ink-800">
                                <span class="block text-sm font-extrabold text-ink-900 dark:text-ink-100">{{ $testimonial->name }}</span>
                                @if ($testimonial->role)
                                    <span class="block mt-0.5 text-xs text-ink-500 dark:text-ink-400">{{ $testimonial->role }}</span>
                                @endif
                            </figcaption>
                        </figure>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- ================= الأسئلة الشائعة ================= --}}
    @if ($this->faqs->isNotEmpty())
        <section class="px-4 py-16 mx-auto max-w-4xl sm:px-6 lg:px-8 lg:py-20">
            <div class="text-center">
                <h2 class="text-2xl font-extrabold text-ink-900 sm:text-3xl dark:text-ink-50">أسئلة شائعة</h2>
                <p class="mt-3 text-base leading-8 text-ink-600 dark:text-ink-400">
                    أكثر ما يُسأل عنه قبل الحجز.
                </p>
            </div>

            <div class="mt-10 space-y-3">
                @foreach ($this->faqs as $faq)
                    <details class="p-5 transition-colors border group rounded-2xl border-ink-200 open:bg-ink-50 dark:border-ink-800 dark:open:bg-ink-900">
                        <summary class="flex cursor-pointer items-center justify-between gap-4 text-sm font-extrabold text-ink-900 marker:content-none dark:text-ink-100">
                            {{ $faq->question }}
                            <span class="transition-transform shrink-0 text-ink-400 group-open:rotate-180">
                                <x-icon name="chevron-down" :size="18" />
                            </span>
                        </summary>

                        <p class="mt-4 text-sm leading-8 text-ink-600 dark:text-ink-400">{{ $faq->answer }}</p>
                    </details>
                @endforeach
            </div>

            <div class="mt-8 text-center">
                <x-ui.button href="{{ route('faq') }}" variant="ghost" icon-after="arrow-left">
                    كل الأسئلة الشائعة
                </x-ui.button>
            </div>
        </section>
    @endif

    <x-site.cta />
</div>
