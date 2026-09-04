<?php

use App\Models\Category;
use App\Models\Faq;
use App\Models\Media;
use App\Models\Post;
use App\Models\Section;
use App\Support\Schema;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * صفحة خدمة التصوير العقاري — صفحة مبيعات لا قائمة أعمال.
 *
 * القسم الفرعي الاعتيادي يعرض بطاقات أعمال وينتهي. هذه الخدمة تحتاج أكثر:
 * ثلاثة عملاء مختلفين يصلون إليها — المطوّر يسأل عن الاتساق بين الوحدات،
 * ومستثمر الإيجار القصير عن غلاف إعلانه، ومالك الوحدة عن الموعد. صفحة واحدة
 * تخاطب الثلاثة معًا تنتهي بألا تخاطب أحدًا، فالمبدّل يعرض لكل زائر إجابته.
 *
 * والصور تُعرض مجموعةً مجموعة لا ألبومًا واحدًا: الزائر يبحث عن مساحة بعينها.
 */
new class extends Component
{
    public Section $section;

    public Category $category;

    public function mount(): void
    {
        $section = Section::where('slug', Section::SERVICES)->firstOrFail();

        $category = Category::query()
            ->where('section_id', $section->id)
            ->where('slug', Category::REAL_ESTATE)
            ->firstOrFail();

        abort_unless($section->is_active && $category->is_active, 404);

        $this->section = $section;
        $this->category = $category;

        // faqPage تعود null حين لا أسئلة، وaddGraph لا تقبل إلا مصفوفات
        $nodes = array_filter([
            Schema::servicePage($category),
            [
                '@type' => 'CollectionPage',
                '@id' => $category->url().'#page',
                'url' => $category->url(),
                'name' => $category->metaTitle(),
                'description' => $category->metaDescription(),
                'inLanguage' => 'ar',
                'isPartOf' => ['@id' => Schema::websiteId()],
                'mainEntity' => [
                    '@type' => 'ItemList',
                    'numberOfItems' => $this->groups->count(),
                    'itemListElement' => $this->groups->values()
                        ->map(fn (Post $p, int $i) => [
                            '@type' => 'ListItem',
                            'position' => $i + 1,
                            'url' => $p->url(),
                            'name' => $p->title,
                        ])->all(),
                ],
            ],
            Schema::faqPage($this->faqs, $category->url()),
        ]);

        seo()
            ->set(
                title: $category->metaTitle(),
                description: $category->metaDescription(),
                imageMedia: $this->hero,
            )
            ->breadcrumbs([
                ['label' => $section->name, 'url' => $section->url()],
                ['label' => $category->name, 'url' => $category->url()],
            ])
            ->addGraph(...$nodes);
    }

    /**
     * المجموعات المعروضة — العمل بلا صور لا يُعرض إطارًا فارغًا.
     *
     * @return Collection<int, Post>
     */
    #[Computed]
    public function groups(): Collection
    {
        return Post::query()
            ->published()
            ->where('category_id', $this->category->id)
            ->whereHas('media')
            ->with('media')
            ->ordered()
            ->get();
    }

    /** صورة الواجهة — أقوى لقطة في الملف، معلَّمة بـ usage لا مختارة بالصدفة. */
    #[Computed]
    public function hero(): ?Media
    {
        return Media::where('usage', 're_hero')->first();
    }

    /**
     * صور المبادئ الثلاثة، مفهرسة بمفتاح المبدأ.
     *
     * @return array<string, Media>
     */
    #[Computed]
    public function craft(): array
    {
        return Media::query()
            ->whereIn('usage', ['re_craft_verticals', 're_craft_bluehour', 're_craft_styling'])
            ->get()
            ->keyBy(fn (Media $m) => (string) $m->usage)
            ->all();
    }

    /**
     * زوج المقارنة: المجلس بالجوال، والمجلس بالكاميرا.
     *
     * المقارنة بين خام الكاميرا ومعالَجه جُرّبت أولًا فسقطت: فرقها فرق معالجة
     * يراه المصوّر ولا يراه المالك. والمالك لا يقارن ملفّين من كاميرا واحدة، بل
     * يقارن ما يلتقطه بجواله بما سيستلمه — وهذه هي المقارنة التي تبيع.
     *
     * القسم لا يظهر حتى تُستورد الصورتان معًا — نصف مقارنة أسوأ من لا مقارنة.
     *
     * @return array{before: Media, after: Media}|null
     */
    #[Computed]
    public function comparison(): ?array
    {
        $before = Media::where('usage', 're_before')->first();
        $after = Media::where('usage', 're_after')->first();

        return $before && $after ? ['before' => $before, 'after' => $after] : null;
    }

    /** @return Collection<int, Faq> */
    #[Computed]
    public function faqs(): Collection
    {
        return Faq::query()
            ->active()
            ->where('category_id', $this->category->id)
            ->ordered()
            ->get();
    }

    #[Computed]
    public function photoCount(): int
    {
        return $this->groups->sum(fn (Post $p) => $p->media->count());
    }

    /**
     * ثلاثة مسارات لثلاثة عملاء.
     *
     * نصّ الصفحة لا محتوى قاعدة البيانات: هذه حجج بيع مرتبطة بتصميم المبدّل،
     * لا أعمال ولا أسئلة تُحرَّر من لوحة التحكم.
     *
     * @return array<int, array{key: string, label: string, hint: string, icon: string, lead: string, points: array<int, array{0: string, 1: string}>, stat: array{0: string, 1: string}}>
     */
    public function tracks(): array
    {
        return [
            [
                'key' => 'dev',
                'label' => 'مطوّر أو شركة تسويق',
                'hint' => 'مشروع كامل، عدّة وحدات',
                'icon' => 'layers',
                'lead' => 'التحدّي في المشروع ليس اللقطة الواحدة بل الاتساق: الوحدات المتشابهة يجب أن تخرج بالإضاءة واللون نفسه حتى تبدو من ملف واحد.',
                'points' => [
                    ['اتساق عبر الوحدات', 'إضاءة ومعالجة موحّدة، فلا تختلف وحدة عن أخرى.'],
                    ['مؤثث وغير مؤثث', 'خبرة في الاثنين — والشاغر أصعب من المفروش لا أسهل.'],
                    ['تسليم خلال أسبوع', 'الصور المعالجة جاهزة للنشر.'],
                ],
                'stat' => ['مدّة التسليم', 'أسبوع'],
            ],
            [
                'key' => 'host',
                'label' => 'مستثمر إيجار قصير',
                'hint' => 'وحدة أو أكثر على منصات الحجز',
                'icon' => 'home',
                'lead' => 'في منصات الحجز الغلاف وحده يقرّر النقرة، وبقية الصور لا يراها إلا من نقر. لذلك يُختار الغلاف معك لا بالصدفة.',
                'points' => [
                    ['الغلاف يُختار معك', 'أهم صورة في الإعلان تستحق قرارًا لا صدفة.'],
                    ['تصوير المرافق', 'غسالة، ركن قهوة، مكتب عمل — الضيف يبحث عنها قبل أن يقرأ الوصف.'],
                    ['تسليم خلال 3 أيام', 'الوحدة تعود للعرض بسرعة.'],
                ],
                'stat' => ['مدّة التسليم', '3 أيام'],
            ],
            [
                'key' => 'owner',
                'label' => 'مالك وحدة',
                'hint' => 'بيع أو تأجير سنوي',
                'icon' => 'user',
                'lead' => 'وحدة واحدة، جلسة واحدة، تسليم سريع. لا تحتاج أكثر من ذلك.',
                'points' => [
                    ['جلسة قصيرة', 'ساعتان إلى ثلاث للشقة الاعتيادية.'],
                    ['عدد صور واضح مسبقًا', 'تعرف ما ستستلمه قبل أن نبدأ.'],
                    ['تسليم خلال 3 أيام', 'جاهزة للنشر مباشرة.'],
                ],
                'stat' => ['مدّة التسليم', '3 أيام'],
            ],
        ];
    }

    /**
     * المبادئ الثلاثة — ما يفصل الصورة الاحترافية عن صورة الجوال.
     *
     * @return array<int, array{slot: string, title: string, body: string}>
     */
    public function principles(): array
    {
        return [
            [
                'slot' => 're_craft_verticals',
                'title' => 'الخطوط الرأسية مستقيمة',
                'body' => 'أول ما تكشفه صورة العقار الهاوية: جدران مائلة وأبواب تضيق نحو الأعلى. تصحيح المنظور يجعل المساحة تبدو كما هي في الواقع.',
            ],
            [
                'slot' => 're_craft_bluehour',
                'title' => 'الساعة الزرقاء لا الظهيرة',
                'body' => 'الواجهات تُصوَّر بعد الغروب بدقائق: إضاءة المبنى تشتعل والسماء لا تزال زرقاء. عشرون دقيقة في اليوم تصنع الفرق كلّه.',
            ],
            [
                'slot' => 're_craft_styling',
                'title' => 'المشهد يُرتَّب قبل التصوير',
                'body' => 'وسادة مائلة أو سلك ظاهر يسحب العين من المساحة إلى الفوضى. الترتيب جزء من العمل لا إضافة عليه.',
            ],
        ];
    }
}; ?>

<div class="sec-theme" style="{{ $section->colorStyle() }}">

    {{-- ================= الواجهة ================= --}}
    <header class="relative overflow-hidden bg-ink-950">
        @if ($this->hero)
            <x-site.picture
                :media="$this->hero"
                variant="full"
                eager
                sizes="(max-width: 640px) 200vw, 100vw"
                class="absolute inset-0 size-full opacity-65"
            />
        @endif

        <div class="absolute inset-0 bg-gradient-to-t from-ink-950 via-ink-950/70 to-ink-950/30" aria-hidden="true"></div>

        <div class="relative px-4 py-20 mx-auto max-w-7xl sm:px-6 lg:px-8 lg:py-28">
            <x-site.breadcrumbs
                :items="[
                    ['label' => $section->name, 'url' => $section->url()],
                    ['label' => $category->name],
                ]"
                class="[&_ol]:text-white/70 [&_a:hover]:text-white"
            />

            <p class="mt-6 text-sm font-bold tracking-wide sec-text">{{ $category->tagline }}</p>

            <h1 class="max-w-3xl mt-3 text-3xl font-extrabold text-white text-balance sm:text-4xl lg:text-5xl">
                التصوير العقاري
            </h1>

            <p class="max-w-2xl mt-5 leading-9 text-white/80">
                شقق وفلل ومكاتب ومجمّعات سكنية، مؤثثة وغير مؤثثة. خطوط رأسية مستقيمة، وإضاءة متوازنة بين
                الداخل والنافذة، وصور جاهزة لمنصات الإدراج والحجز بلا معالجة إضافية.
            </p>

            <div class="flex flex-wrap gap-3 mt-8">
                <x-ui.button
                    href="{{ whatsapp_url('السلام عليكم، أرغب في الاستفسار عن تصوير عقار') }}"
                    variant="whatsapp"
                    icon="whatsapp"
                    :navigate="false"
                    target="_blank"
                    rel="noopener"
                >
                    اطلب عرض سعر
                </x-ui.button>

                <x-ui.button href="{{ route('contact') }}" variant="outline-light" icon="send">نموذج الحجز</x-ui.button>
            </div>
        </div>
    </header>

    <div class="px-4 mx-auto max-w-7xl sm:px-6 lg:px-8">

        {{-- ================= ثلاثة مسارات ================= --}}
        <section
            class="py-14 lg:py-20"
            x-data="{ track: 'host' }"
            aria-labelledby="tracks-heading"
        >
            <h2 id="tracks-heading" class="text-xl font-extrabold text-ink-900 sm:text-2xl dark:text-ink-50">
                ما الذي تصوّره؟
            </h2>
            <p class="max-w-2xl mt-2 text-sm leading-8 text-ink-600 dark:text-ink-400">
                لكل عميل سؤاله. اختر ما ينطبق عليك ليظهر ما يخصّك وحده.
            </p>

            <div class="grid gap-3 mt-6 sm:grid-cols-3" role="tablist" aria-label="نوع العميل">
                @foreach ($this->tracks() as $track)
                    <button
                        type="button"
                        role="tab"
                        x-on:click="track = '{{ $track['key'] }}'"
                        x-bind:aria-selected="track === '{{ $track['key'] }}' ? 'true' : 'false'"
                        x-bind:class="track === '{{ $track['key'] }}'
                            ? 'sec-border sec-bg-soft'
                            : 'border-ink-200 hover:border-ink-300 dark:border-ink-800 dark:hover:border-ink-700'"
                        class="flex items-start gap-3 p-4 text-right transition-colors border rounded-2xl focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-500"
                    >
                        <span class="flex items-center justify-center rounded-lg size-9 shrink-0 sec-bg-soft sec-text">
                            <x-icon :name="$track['icon']" :size="18" />
                        </span>
                        <span>
                            <span class="block text-sm font-extrabold text-ink-900 dark:text-ink-100">{{ $track['label'] }}</span>
                            <span class="block mt-0.5 text-xs text-ink-500 dark:text-ink-400">{{ $track['hint'] }}</span>
                        </span>
                    </button>
                @endforeach
            </div>

            @foreach ($this->tracks() as $track)
                <div
                    x-show="track === '{{ $track['key'] }}'"
                    x-cloak
                    role="tabpanel"
                    class="grid gap-8 p-6 mt-4 border sm:p-8 rounded-3xl border-ink-200 bg-ink-50 lg:grid-cols-3 dark:border-ink-800 dark:bg-ink-900"
                >
                    <div class="lg:col-span-2">
                        <p class="leading-9 text-ink-700 dark:text-ink-300">{{ $track['lead'] }}</p>

                        <ul class="mt-6 space-y-4">
                            @foreach ($track['points'] as [$pointTitle, $pointBody])
                                <li class="flex gap-3">
                                    <span class="mt-1 sec-text shrink-0"><x-icon name="check-circle" :size="18" /></span>
                                    <span>
                                        <span class="block text-sm font-extrabold text-ink-900 dark:text-ink-100">{{ $pointTitle }}</span>
                                        <span class="block mt-1 text-sm leading-7 text-ink-600 dark:text-ink-400">{{ $pointBody }}</span>
                                    </span>
                                </li>
                            @endforeach
                        </ul>
                    </div>

                    <div class="flex flex-col gap-4">
                        <div class="p-5 bg-white border rounded-2xl border-ink-200 dark:border-ink-800 dark:bg-ink-950">
                            <p class="text-xs font-bold text-ink-500 dark:text-ink-400">{{ $track['stat'][0] }}</p>
                            <p class="mt-1 text-2xl font-extrabold sec-text">{{ $track['stat'][1] }}</p>
                        </div>

                        <div class="p-5 bg-white border rounded-2xl border-ink-200 dark:border-ink-800 dark:bg-ink-950">
                            <p class="text-xs font-bold text-ink-500 dark:text-ink-400">السعر</p>
                            <p class="mt-1 text-sm font-bold leading-7 text-ink-800 dark:text-ink-200">
                                يُحدَّد بحسب تفاصيل الطلب
                            </p>
                        </div>
                    </div>
                </div>
            @endforeach
        </section>

        {{-- ================= المبادئ ================= --}}
        <section class="py-14 lg:py-20 border-t border-ink-200 dark:border-ink-800" aria-labelledby="craft-heading">
            <h2 id="craft-heading" class="text-xl font-extrabold text-ink-900 sm:text-2xl dark:text-ink-50">
                ثلاثة أشياء تفصل الصورة الاحترافية عن غيرها
            </h2>

            <div class="grid gap-8 mt-8 sm:grid-cols-3">
                @foreach ($this->principles() as $index => $principle)
                    <figure>
                        <div class="overflow-hidden bg-ink-100 rounded-2xl aspect-4/3 dark:bg-ink-800">
                            <x-site.picture
                                :media="$this->craft[$principle['slot']] ?? null"
                                variant="md"
                                sizes="(min-width: 640px) 33vw, 100vw"
                                class="size-full"
                            />
                        </div>

                        <figcaption class="mt-4">
                            <span class="text-xs font-extrabold sec-text">{{ str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) }}</span>
                            <h3 class="mt-1 text-base font-extrabold text-ink-900 dark:text-ink-100">{{ $principle['title'] }}</h3>
                            <p class="mt-2 text-sm leading-7 text-ink-600 dark:text-ink-400">{{ $principle['body'] }}</p>
                        </figcaption>
                    </figure>
                @endforeach
            </div>
        </section>

        {{-- ================= قبل وبعد ================= --}}
        @if ($this->comparison)
            <section
                class="py-14 lg:py-20 border-t border-ink-200 dark:border-ink-800"
                x-data="{ split: 55, dragging: false }"
                aria-labelledby="compare-heading"
            >
                <h2 id="compare-heading" class="text-xl font-extrabold text-ink-900 sm:text-2xl dark:text-ink-50">
                    المجلس نفسه: بالجوال، وبالعدسة
                </h2>
                <p class="max-w-2xl mt-2 text-sm leading-8 text-ink-600 dark:text-ink-400">
                    اسحب المقبض لترى الفرق. «قبل» صورة بالجوال كما يصوّر المالك وحدته عادةً، و«بعد» اللقطة
                    نفسها بالكاميرا: عدسة واسعة، وخطوط رأسية مستقيمة، وإضاءة متوازنة بين المصابيح والنافذة.
                </p>

                {{--
                    الاتجاه من اليمين: المنطقة المكشوفة تنمو من بداية السطر لا من
                    يساره، فحساب الموضع يقيس من الحافة اليمنى.

                    وصورة «قبل» تجلس في غلاف عرضه 10000/split بالمئة من حاوية
                    القصّ — أي عرض الإطار كاملًا مهما كان موضع المقبض. حسابٌ
                    بالنِّسب لا بالبكسل، فلا يحتاج قياس العنصر ولا يختلّ عند تغيير
                    عرض الشاشة. ولأن اللقطتين قد تختلفان في نسبة الأبعاد — واحدة
                    بالجوال وأخرى بالكاميرا — تملأ الصورة غلافها بـ object-cover
                    فيبقى الحدّ بينهما مستقيمًا لا مكسورًا.
                --}}
                <div
                    class="relative mt-8 overflow-hidden select-none rounded-3xl bg-ink-100 dark:bg-ink-800"
                    x-on:pointermove="dragging && (split = Math.min(100, Math.max(0, (($el.getBoundingClientRect().right - $event.clientX) / $el.offsetWidth) * 100)))"
                    x-on:pointerup.window="dragging = false"
                >
                    <x-site.picture
                        :media="$this->comparison['after']"
                        variant="lg"
                        sizes="100vw"
                        class="w-full"
                    />

                    <div class="absolute inset-y-0 start-0 overflow-hidden" x-bind:style="`width: ${split}%`">
                        <div
                            class="absolute inset-y-0 start-0"
                            x-bind:style="`width: ${10000 / Math.max(split, 0.01)}%`"
                        >
                            <x-site.picture
                                :media="$this->comparison['before']"
                                variant="lg"
                                sizes="100vw"
                                class="size-full"
                            />
                        </div>
                    </div>

                    <div
                        class="absolute inset-y-0 w-1 -translate-x-1/2 bg-white cursor-ew-resize"
                        x-bind:style="`inset-inline-start: ${split}%`"
                        x-on:pointerdown="dragging = true"
                    >
                        <span class="absolute flex items-center justify-center -translate-x-1/2 -translate-y-1/2 bg-white rounded-full shadow-lg top-1/2 left-1/2 size-10 text-ink-800">
                            <x-icon name="chevron-right" :size="16" />
                            <x-icon name="chevron-left" :size="16" />
                        </span>
                    </div>

                    <span class="absolute px-3 py-1 text-xs font-bold text-white rounded-full top-4 start-4 bg-ink-950/70">قبل</span>
                    <span class="absolute px-3 py-1 text-xs font-bold text-white rounded-full top-4 end-4 bg-ink-950/70">بعد</span>
                </div>

                <label class="block mt-4">
                    <span class="sr-only">موضع المقارنة</span>
                    <input type="range" min="0" max="100" x-model="split" class="w-full accent-brand-500">
                </label>
            </section>
        @endif

        {{-- ================= المجموعات ================= --}}
        @if ($this->groups->isNotEmpty())
            <section class="py-14 lg:py-20 border-t border-ink-200 dark:border-ink-800" aria-labelledby="work-heading">
                <div class="flex items-end justify-between gap-4">
                    <h2 id="work-heading" class="text-xl font-extrabold text-ink-900 sm:text-2xl dark:text-ink-50">
                        من الأعمال
                    </h2>
                    <p class="text-sm text-ink-500 shrink-0 dark:text-ink-400">{{ $this->photoCount }} صورة</p>
                </div>

                <div class="space-y-16 mt-10">
                    @foreach ($this->groups as $group)
                        <article>
                            <h3 class="text-lg font-extrabold text-ink-900 dark:text-ink-100">
                                <a href="{{ $group->url() }}" wire:navigate class="transition-colors hover:sec-text">
                                    {{ $group->title }}
                                </a>
                            </h3>

                            @if ($group->excerpt)
                                <p class="max-w-3xl mt-2 text-sm leading-8 text-ink-600 dark:text-ink-400">{{ $group->excerpt }}</p>
                            @endif

                            <x-site.gallery :media="$group->media" class="mt-6" />
                        </article>
                    @endforeach
                </div>
            </section>
        @else
            <x-site.empty-state
                class="my-16"
                :icon="$category->icon"
                title="لا توجد صور منشورة في هذه الصفحة بعد"
                description="أضف الأعمال وصورها من لوحة التحكم لتظهر هنا مجموعةً مجموعة."
            />
        @endif

        {{-- ================= أسئلة شائعة ================= --}}
        @if ($this->faqs->isNotEmpty())
            <section class="py-14 lg:py-20 border-t border-ink-200 dark:border-ink-800" aria-labelledby="faq-heading">
                <h2 id="faq-heading" class="text-xl font-extrabold text-ink-900 sm:text-2xl dark:text-ink-50">
                    أسئلة قبل الحجز
                </h2>

                <div class="max-w-3xl mt-6 space-y-3">
                    @foreach ($this->faqs as $faq)
                        <details class="p-5 border rounded-2xl group border-ink-200 open:bg-ink-50 dark:border-ink-800 dark:open:bg-ink-900">
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
            </section>
        @endif

        {{-- ================= خدمات أخرى ================= --}}
        @php($siblings = $section->activeCategories()->where('id', '!=', $category->id)->get())

        @if ($siblings->isNotEmpty())
            <section class="pb-16 border-t border-ink-200 pt-14 dark:border-ink-800">
                <h2 class="text-lg font-extrabold text-ink-900 dark:text-ink-50">خدمات أخرى</h2>

                <div class="grid gap-4 mt-5 sm:grid-cols-3">
                    @foreach ($siblings as $sibling)
                        <a
                            href="{{ $sibling->url() }}"
                            wire:navigate
                            class="flex items-center gap-3 p-4 transition-colors border group rounded-xl border-ink-200 hover:border-brand-400 hover:bg-ink-50 dark:border-ink-800 dark:hover:border-brand-600 dark:hover:bg-ink-900"
                        >
                            <span class="flex items-center justify-center rounded-lg size-9 bg-ink-100 text-ink-600 dark:bg-ink-800 dark:text-ink-400">
                                <x-icon :name="$sibling->icon" :size="17" />
                            </span>
                            <span class="text-sm font-bold text-ink-800 dark:text-ink-200">{{ $sibling->name }}</span>
                        </a>
                    @endforeach
                </div>
            </section>
        @endif
    </div>

    <x-site.cta
        title="عندك عقار يحتاج صورًا تبيعه؟"
        description="أرسل موقع العقار ونوعه وعدد الغرف، ويصلك عرض سعر ومدّة تسليم في نفس اليوم."
        compact
    />
</div>
