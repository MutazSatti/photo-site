<?php

use App\Models\Category;
use App\Models\Faq;
use App\Models\Media;
use App\Models\PageBlock;
use App\Models\Post;
use App\Models\Section;
use App\Support\Schema;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * صفحة الزواجات والمناسبات — صفحة مبيعات لا قائمة أعمال.
 *
 * ثلاثة عملاء مختلفين يصلون إليها: عريس يسأل عن ليلته، وعائلة أو مدرسة تسأل عن
 * حفل التخرّج، وجهة تسأل عن الافتتاح. صفحة واحدة تخاطب الثلاثة معًا تنتهي بألا
 * تخاطب أحدًا، فالمبدّل يعرض لكل زائر جوابه.
 *
 * ولا شيء في نصّها مكتوب هنا: العناوين والفقرات والمراحل والأرقام كلها صفوف في
 * page_blocks، وترتيب الأقسام وظهورها منها كذلك. القالب يعرف كيف يرسم كل قسم،
 * ولا يعرف ماذا يقول ولا أين يقع — وذلك ما يجعل الصفحة قابلة للتحرير من اللوحة
 * بلا مبرمج.
 *
 * التغطية للقسم الرجالي: لا كوشة ولا خروج، فتلك من نصيب تغطية العروس.
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
            ->where('slug', Category::EVENTS)
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
                    'numberOfItems' => $this->allGroups->count(),
                    'itemListElement' => $this->allGroups->values()
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
     * أقسام الصفحة بترتيبها من اللوحة.
     *
     * @return Collection<int, PageBlock>
     */
    #[Computed]
    public function blocks(): Collection
    {
        return PageBlock::visible(PageBlock::EVENTS);
    }

    /** صورة الواجهة — تُبدَّل من «صور الواجهة» في اللوحة. */
    #[Computed]
    public function hero(): ?Media
    {
        return Media::where('usage', 'ev_hero')->first();
    }

    /**
     * مجموعات قسم معرض بعينه، بترتيب عناصره.
     *
     * المجموعة عملٌ في المعرض، فإخفاؤها أو إعادة ترتيب صورها يتمّ من «الأعمال
     * والمحتوى» كأي عمل. وما ليس منشورًا أو ليس فيه صورة لا يُعرض إطارًا فارغًا.
     *
     * @return Collection<int, Post>
     */
    public function groupsIn(PageBlock $block): Collection
    {
        $posts = $block->items
            ->map(fn ($item) => $item->post)
            ->filter(fn (?Post $post) => $post !== null
                && $post->status === 'published'
                && $post->media->isNotEmpty());

        return new Collection($posts->values()->all());
    }

    /**
     * كل مجموعات الصفحة — للبيانات المهيكلة وعدّ الصور.
     *
     * @return Collection<int, Post>
     */
    #[Computed]
    public function allGroups(): Collection
    {
        $posts = $this->blocks
            ->filter(fn (PageBlock $b) => $b->sourceCategory() !== null)
            ->flatMap(fn (PageBlock $b) => $this->groupsIn($b));

        return new Collection($posts->values()->all());
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
        return $this->allGroups->sum(fn (Post $p) => $p->media->count());
    }
}; ?>

<div class="sec-theme" style="{{ $section->colorStyle() }}">
    @foreach ($this->blocks as $block)
        @switch($block->key)

            {{-- ================= الواجهة ================= --}}
            @case('hero')
                <header class="relative overflow-hidden bg-ink-950">
                    @if ($this->hero)
                        <x-site.picture
                            :media="$this->hero"
                            variant="full"
                            eager
                            sizes="(max-width: 640px) 200vw, 100vw"
                            class="absolute inset-0 size-full opacity-60"
                        />
                    @endif

                    <div class="absolute inset-0 bg-gradient-to-t from-ink-950 via-ink-950/75 to-ink-950/40" aria-hidden="true"></div>

                    <div class="relative px-4 py-20 mx-auto max-w-7xl sm:px-6 lg:px-8 lg:py-28">
                        <x-site.breadcrumbs
                            :items="[
                                ['label' => $section->name, 'url' => $section->url()],
                                ['label' => $category->name],
                            ]"
                            class="[&_ol]:text-white/70 [&_a:hover]:text-white"
                        />

                        @if ($block->intro())
                            <p class="mt-6 text-sm font-bold tracking-wide sec-text">{{ $block->intro() }}</p>
                        @endif

                        <h1 class="max-w-3xl mt-3 text-3xl font-extrabold text-white text-balance sm:text-4xl lg:text-5xl">
                            {{ $block->heading() }}
                        </h1>

                        @if ($block->text())
                            <p class="max-w-2xl mt-5 leading-9 text-white/80">{{ $block->text() }}</p>
                        @endif

                        <div class="flex flex-wrap gap-3 mt-8">
                            <x-ui.button
                                href="{{ whatsapp_url('السلام عليكم، أرغب في الاستفسار عن تغطية مناسبة') }}"
                                variant="whatsapp"
                                icon="whatsapp"
                                :navigate="false"
                                target="_blank"
                                rel="noopener"
                            >
                                تواصل عبر الواتساب
                            </x-ui.button>

                            <x-ui.button href="#works" variant="outline-light" icon="images" :navigate="false">
                                شاهد الأعمال
                            </x-ui.button>
                        </div>
                    </div>
                </header>
                @break

            {{-- ================= شريط الأرقام ================= --}}
            @case('figures')
                @if ($block->items->isNotEmpty())
                    @php($figureCols = match (min($block->items->count(), 4)) {
                        1 => '',
                        2 => 'sm:grid-cols-2',
                        3 => 'sm:grid-cols-2 lg:grid-cols-3',
                        default => 'sm:grid-cols-2 lg:grid-cols-4',
                    })

                    <div class="border-b bg-ink-50 border-ink-200 dark:bg-ink-900 dark:border-ink-800">
                        <div class="grid gap-6 px-4 py-8 mx-auto max-w-7xl sm:px-6 lg:px-8 {{ $figureCols }}">
                            @foreach ($block->items as $figure)
                                <div class="flex items-center gap-4">
                                    <span class="flex items-center justify-center rounded-xl size-11 shrink-0 sec-bg-soft sec-text">
                                        <x-icon :name="$figure->icon ?? 'star'" :size="20" />
                                    </span>
                                    <span>
                                        <span class="block text-lg font-extrabold text-ink-900 dark:text-ink-50">{{ $figure->heading() }}</span>
                                        <span class="block mt-0.5 text-xs text-ink-500 dark:text-ink-400">{{ $figure->line() }}</span>
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
                @break

            {{-- ================= المبدّل: ما المناسبة؟ ================= --}}
            @case('tracks')
                @if ($block->items->isNotEmpty())
                    <div class="px-4 mx-auto max-w-7xl sm:px-6 lg:px-8">
                        <section
                            class="py-14 lg:py-20"
                            x-data="{ track: {{ $block->items->first()->id ?? 0 }} }"
                            aria-labelledby="tracks-heading"
                        >
                            <h2 id="tracks-heading" class="text-xl font-extrabold text-ink-900 sm:text-2xl dark:text-ink-50">
                                {{ $block->heading() }}
                            </h2>

                            @if ($block->intro())
                                <p class="max-w-2xl mt-2 text-sm leading-8 text-ink-600 dark:text-ink-400">{{ $block->intro() }}</p>
                            @endif

                            @php($trackCols = match (min($block->items->count(), 3)) {
                                1 => '',
                                2 => 'sm:grid-cols-2',
                                default => 'sm:grid-cols-3',
                            })

                            <div class="grid gap-3 mt-6 {{ $trackCols }}" role="tablist" aria-label="نوع المناسبة">
                                @foreach ($block->items as $track)
                                    <button
                                        type="button"
                                        role="tab"
                                        x-on:click="track = {{ $track->id }}"
                                        x-bind:aria-selected="track === {{ $track->id }} ? 'true' : 'false'"
                                        x-bind:class="track === {{ $track->id }}
                                            ? 'sec-border sec-bg-soft'
                                            : 'border-ink-200 hover:border-ink-300 dark:border-ink-800 dark:hover:border-ink-700'"
                                        class="flex items-start gap-3 p-4 text-right transition-colors border rounded-2xl focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-500"
                                    >
                                        <span class="flex items-center justify-center rounded-lg size-9 shrink-0 sec-bg-soft sec-text">
                                            <x-icon :name="$track->icon ?? 'camera'" :size="18" />
                                        </span>
                                        <span>
                                            <span class="block text-sm font-extrabold text-ink-900 dark:text-ink-100">{{ $track->heading() }}</span>
                                            <span class="block mt-0.5 text-xs text-ink-500 dark:text-ink-400">{{ $track->line() }}</span>
                                        </span>
                                    </button>
                                @endforeach
                            </div>

                            @foreach ($block->items as $track)
                                <div
                                    x-show="track === {{ $track->id }}"
                                    x-cloak
                                    role="tabpanel"
                                    class="p-6 mt-4 border sm:p-8 rounded-3xl border-ink-200 bg-ink-50 dark:border-ink-800 dark:bg-ink-900"
                                >
                                    @if ($track->text())
                                        <p class="max-w-3xl leading-9 text-ink-700 dark:text-ink-300">{{ $track->text() }}</p>
                                    @endif

                                    @if ($track->children->isNotEmpty())
                                        <ul class="grid gap-4 mt-6 sm:grid-cols-2 lg:grid-cols-3">
                                            @foreach ($track->children as $point)
                                                <li class="flex gap-3">
                                                    <span class="mt-1 sec-text shrink-0"><x-icon name="check-circle" :size="18" /></span>
                                                    <span>
                                                        <span class="block text-sm font-extrabold text-ink-900 dark:text-ink-100">{{ $point->heading() }}</span>
                                                        <span class="block mt-1 text-sm leading-7 text-ink-600 dark:text-ink-400">{{ $point->line() }}</span>
                                                    </span>
                                                </li>
                                            @endforeach
                                        </ul>
                                    @endif
                                </div>
                            @endforeach
                        </section>
                    </div>
                @endif
                @break

            {{-- ================= مراحل الليلة ================= --}}
            @case('timeline')
                @if ($block->items->isNotEmpty())
                    <div class="px-4 mx-auto max-w-7xl sm:px-6 lg:px-8">
                        <section class="py-14 lg:py-20 border-t border-ink-200 dark:border-ink-800" aria-labelledby="timeline-heading">
                            <h2 id="timeline-heading" class="text-xl font-extrabold text-ink-900 sm:text-2xl dark:text-ink-50">
                                {{ $block->heading() }}
                            </h2>

                            @if ($block->intro())
                                <p class="max-w-2xl mt-2 text-sm leading-8 text-ink-600 dark:text-ink-400">{{ $block->intro() }}</p>
                            @endif

                            <ol class="grid gap-4 mt-8 sm:grid-cols-2 lg:grid-cols-4">
                                @foreach ($block->items as $index => $stage)
                                    <li class="relative p-5 border rounded-2xl border-ink-200 dark:border-ink-800">
                                        <span class="flex items-center justify-center rounded-xl size-10 sec-bg-soft sec-text">
                                            <x-icon :name="$stage->icon ?? 'camera'" :size="19" />
                                        </span>

                                        <span class="absolute text-xs font-extrabold top-5 end-5 text-ink-300 dark:text-ink-700">
                                            {{ str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) }}
                                        </span>

                                        <h3 class="mt-4 text-sm font-extrabold text-ink-900 dark:text-ink-100">{{ $stage->heading() }}</h3>
                                        <p class="mt-1 text-xs leading-6 text-ink-500 dark:text-ink-400">{{ $stage->line() }}</p>
                                    </li>
                                @endforeach
                            </ol>
                        </section>
                    </div>
                @endif
                @break

            {{-- ================= المعارض ================= --}}
            @case('weddings')
            @case('corporate')
                @php($groups = $this->groupsIn($block))

                @if ($groups->isNotEmpty())
                    <div class="px-4 mx-auto max-w-7xl sm:px-6 lg:px-8">
                        <section
                            @if ($block->key === 'weddings') id="works" @endif
                            class="py-14 lg:py-20 border-t border-ink-200 dark:border-ink-800"
                        >
                            <div class="flex items-end justify-between gap-4">
                                <h2 class="text-xl font-extrabold text-ink-900 sm:text-2xl dark:text-ink-50">
                                    {{ $block->heading() }}
                                </h2>
                                <p class="text-sm text-ink-500 shrink-0 dark:text-ink-400">
                                    {{ photo_count($groups->sum(fn ($group) => $group->media->count())) }}
                                </p>
                            </div>

                            @if ($block->intro())
                                <p class="max-w-2xl mt-2 text-sm leading-8 text-ink-600 dark:text-ink-400">{{ $block->intro() }}</p>
                            @endif

                            <div class="mt-10 space-y-16">
                                @foreach ($groups as $group)
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
                    </div>
                @endif
                @break

            {{-- ================= أسئلة قبل الحجز ================= --}}
            @case('faq')
                @if ($this->faqs->isNotEmpty())
                    <div class="px-4 mx-auto max-w-7xl sm:px-6 lg:px-8">
                        <section class="py-14 lg:py-20 border-t border-ink-200 dark:border-ink-800" aria-labelledby="faq-heading">
                            <h2 id="faq-heading" class="text-xl font-extrabold text-ink-900 sm:text-2xl dark:text-ink-50">
                                {{ $block->heading() }}
                            </h2>

                            @if ($block->intro())
                                <p class="max-w-2xl mt-2 text-sm leading-8 text-ink-600 dark:text-ink-400">{{ $block->intro() }}</p>
                            @endif

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
                    </div>
                @endif
                @break

            {{-- ================= دعوة للحجز ================= --}}
            @case('cta')
                <x-site.cta :title="$block->heading()" :description="$block->intro()" compact />
                @break

        @endswitch
    @endforeach

    @if ($this->allGroups->isEmpty())
        <div class="px-4 mx-auto max-w-7xl sm:px-6 lg:px-8">
            <x-site.empty-state
                class="my-16"
                :icon="$category->icon"
                title="لا توجد صور منشورة في هذه الصفحة بعد"
                description="أضف المجموعات وصورها من لوحة التحكم لتظهر هنا مجموعةً مجموعة."
            />
        </div>
    @endif
</div>
