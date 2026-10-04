<?php

use App\Models\Category;
use App\Models\Media;
use App\Models\PageBlock;
use App\Models\Post;
use App\Models\Section;
use App\Support\Schema;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * صفحة تصوير الفعاليات والمؤتمرات — صفحة خدمة لا قائمة أعمال.
 *
 * من يبحث عن «مصوّر فعاليات» أو «تصوير مؤتمرات» يسأل أربعة أسئلة بالترتيب: هل
 * تصوّر فعاليتي؟ وماذا سيصلني؟ ومتى؟ ولماذا أنت؟ فبنية الصفحة هي هذا الترتيب،
 * وما بينها الأعمال تُثبت الكلام.
 *
 * والعنوان يسمّي الخدمة لا القسم: «الفعاليات في جدة» اسم تصنيفٍ في المعرض، ولا
 * يقول للباحث ولا لمحرّك البحث ماذا يُقدَّم هنا.
 *
 * ولا جملة من نصّها مكتوبة في هذا الملف: كلها صفوف في page_blocks تُحرَّر من
 * «محتوى الصفحات»، وترتيب الأقسام وظهورها منها كذلك.
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
            ->where('slug', Category::ACTIVITIES)
            ->firstOrFail();

        abort_unless($section->is_active && $category->is_active, 404);

        $this->section = $section;
        $this->category = $category;

        seo()
            ->set(
                title: $category->metaTitle(),
                description: $category->metaDescription(),
                imageMedia: $this->heroMedia,
            )
            ->breadcrumbs([
                ['label' => $section->name, 'url' => $section->url()],
                ['label' => $category->name, 'url' => $category->url()],
            ])
            ->addGraph(
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
            );
    }

    /**
     * أقسام الصفحة بترتيبها من اللوحة.
     *
     * @return Collection<int, PageBlock>
     */
    #[Computed]
    public function blocks(): Collection
    {
        return PageBlock::visible(PageBlock::ACTIVITIES);
    }

    /** صورة الواجهة المرفوعة — تُبدَّل من «صور الواجهة». */
    #[Computed]
    public function heroMedia(): ?Media
    {
        return Media::where('usage', 'ac_hero')->first();
    }

    /**
     * صورة الواجهة الأصلية المشحونة مع الشيفرة.
     *
     * الصفحة المخصّصة لا تمرّ بمكوّن الترويسة العام، فلو اكتفت بخانة الرفع
     * لخرجت بواجهة سوداء على تثبيت لم يُرفع فيه شيء — والملف موجود أصلًا.
     */
    public function heroFallback(): ?string
    {
        return header_photo_default($this->category->slug);
    }

    /**
     * مجموعات قسم المعرض بترتيب عناصره.
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
     * كل مجموعات الصفحة — للبيانات المهيكلة.
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
}; ?>

<div class="sec-theme" style="{{ $section->colorStyle() }}">
    @foreach ($this->blocks as $block)
        @switch($block->key)

            {{-- ================= الواجهة ================= --}}
            @case('hero')
                <header class="relative overflow-hidden bg-ink-950">
                    @if ($this->heroMedia)
                        <x-site.picture
                            :media="$this->heroMedia"
                            variant="full"
                            eager
                            sizes="(max-width: 640px) 200vw, 100vw"
                            class="absolute inset-0 size-full opacity-55"
                        />
                    @elseif ($this->heroFallback())
                        {{-- زينة لا محتوى: العنوان تحتها يقول ما تقوله --}}
                        <img
                            src="{{ $this->heroFallback() }}"
                            alt=""
                            aria-hidden="true"
                            loading="eager"
                            decoding="sync"
                            fetchpriority="high"
                            class="absolute inset-0 object-cover size-full opacity-55"
                        >
                    @endif

                    <div class="absolute inset-0 bg-gradient-to-t from-ink-950 via-ink-950/75 to-ink-950/35" aria-hidden="true"></div>

                    <div class="relative px-4 py-16 mx-auto max-w-7xl sm:px-6 lg:px-8 lg:py-24">
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
                            <p class="max-w-3xl mt-6 leading-9 text-white/80">{{ $block->text() }}</p>
                        @endif

                        <div class="flex flex-wrap gap-3 mt-8">
                            <x-ui.button
                                href="{{ whatsapp_url('السلام عليكم، أرغب في عرض سعر لتصوير فعالية') }}"
                                variant="whatsapp"
                                icon="whatsapp"
                                :navigate="false"
                                target="_blank"
                                rel="noopener"
                            >
                                اطلب عرض سعر
                            </x-ui.button>

                            <x-ui.button href="#works" variant="outline-light" icon="images" :navigate="false">
                                شاهد الأعمال
                            </x-ui.button>
                        </div>
                    </div>
                </header>
                @break

            {{-- ========= خدمات التغطية · ما تشمله · لماذا أنا ========= --}}
            @case('services')
            @case('coverage')
            @case('why')
                @if ($block->items->isNotEmpty())
                    <div class="px-4 mx-auto max-w-7xl sm:px-6 lg:px-8">
                        <section
                            class="py-14 lg:py-20 @if ($block->key !== 'services') border-t border-ink-200 dark:border-ink-800 @endif"
                            aria-labelledby="block-{{ $block->key }}"
                        >
                            <h2 id="block-{{ $block->key }}" class="max-w-3xl text-xl font-extrabold text-ink-900 text-balance sm:text-2xl dark:text-ink-50">
                                {{ $block->heading() }}
                            </h2>

                            @if ($block->intro())
                                <p class="max-w-2xl mt-2 text-sm leading-8 text-ink-600 dark:text-ink-400">{{ $block->intro() }}</p>
                            @endif

                            <div class="grid gap-4 mt-8 sm:grid-cols-2 lg:grid-cols-3">
                                @foreach ($block->items as $item)
                                    <div class="p-5 border rounded-2xl border-ink-200 dark:border-ink-800">
                                        <span class="flex items-center justify-center rounded-xl size-10 sec-bg-soft sec-text">
                                            <x-icon :name="$item->icon ?? 'camera'" :size="19" />
                                        </span>

                                        <h3 class="mt-4 text-sm font-extrabold text-ink-900 dark:text-ink-100">{{ $item->heading() }}</h3>
                                        <p class="mt-1.5 text-sm leading-7 text-ink-600 dark:text-ink-400">{{ $item->line() }}</p>
                                    </div>
                                @endforeach
                            </div>
                        </section>
                    </div>
                @endif
                @break

            {{-- ================= التسليم ================= --}}
            @case('delivery')
                <div class="px-4 mx-auto max-w-7xl sm:px-6 lg:px-8">
                    <section class="py-14 lg:py-20 border-t border-ink-200 dark:border-ink-800" aria-labelledby="block-delivery">
                        <div class="grid gap-10 lg:grid-cols-2">
                            <div>
                                <h2 id="block-delivery" class="text-xl font-extrabold text-ink-900 sm:text-2xl dark:text-ink-50">
                                    {{ $block->heading() }}
                                </h2>

                                @if ($block->intro())
                                    <p class="mt-2 text-sm font-bold sec-text">{{ $block->intro() }}</p>
                                @endif

                                @if ($block->text())
                                    <p class="mt-5 leading-9 text-ink-700 dark:text-ink-300">{{ $block->text() }}</p>
                                @endif
                            </div>

                            @if ($block->items->isNotEmpty())
                                <ol class="space-y-4">
                                    @foreach ($block->items as $index => $step)
                                        <li class="flex gap-4 p-5 border rounded-2xl border-ink-200 bg-ink-50 dark:border-ink-800 dark:bg-ink-900">
                                            <span class="flex items-center justify-center rounded-xl size-10 shrink-0 sec-bg-soft sec-text">
                                                <x-icon :name="$step->icon ?? 'clock'" :size="19" />
                                            </span>

                                            <span>
                                                <span class="block text-sm font-extrabold text-ink-900 dark:text-ink-100">
                                                    {{ $step->heading() }}
                                                </span>
                                                <span class="block mt-1 text-sm leading-7 text-ink-600 dark:text-ink-400">
                                                    {{ $step->line() }}
                                                </span>
                                            </span>
                                        </li>
                                    @endforeach
                                </ol>
                            @endif
                        </div>
                    </section>
                </div>
                @break

            {{-- ================= الأعمال السابقة ================= --}}
            @case('works')
                @php($groups = $this->groupsIn($block))

                @if ($groups->isNotEmpty())
                    <div class="px-4 mx-auto max-w-7xl sm:px-6 lg:px-8">
                        <section id="works" class="py-14 lg:py-20 border-t border-ink-200 dark:border-ink-800" aria-labelledby="block-works">
                            <div class="flex items-end justify-between gap-4">
                                <h2 id="block-works" class="text-xl font-extrabold text-ink-900 sm:text-2xl dark:text-ink-50">
                                    {{ $block->heading() }}
                                </h2>
                                <p class="text-sm text-ink-500 shrink-0 dark:text-ink-400">
                                    {{ photo_count($groups->sum(fn ($group) => $group->media->count())) }}
                                </p>
                            </div>

                            @if ($block->intro())
                                <p class="max-w-2xl mt-2 text-sm leading-8 text-ink-600 dark:text-ink-400">{{ $block->intro() }}</p>
                            @endif

                            <div class="mt-10 space-y-14">
                                @foreach ($groups as $group)
                                    <article>
                                        <div class="flex flex-wrap items-center gap-3">
                                            <h3 class="text-lg font-extrabold text-ink-900 dark:text-ink-100">
                                                <a href="{{ $group->url() }}" wire:navigate class="transition-colors hover:sec-text">
                                                    {{ $group->title }}
                                                </a>
                                            </h3>

                                            @if ($group->location)
                                                <span class="inline-flex items-center gap-1 text-xs text-ink-500 dark:text-ink-400">
                                                    <x-icon name="map-pin" :size="14" />{{ $group->location }}
                                                </span>
                                            @endif

                                            <span class="text-xs text-ink-400 dark:text-ink-500">
                                                {{ photo_count($group->media->count()) }}
                                            </span>
                                        </div>

                                        @if ($group->excerpt)
                                            <p class="max-w-3xl mt-2 text-sm leading-8 text-ink-600 dark:text-ink-400">{{ $group->excerpt }}</p>
                                        @endif

                                        <x-site.gallery :media="$group->media" class="mt-5" />

                                        <a
                                            href="{{ $group->url() }}"
                                            wire:navigate
                                            class="inline-flex items-center gap-1.5 mt-4 text-sm font-bold sec-text hover:underline"
                                        >
                                            تفاصيل العمل: {{ $group->title }}
                                            <x-icon name="arrow-left" :size="15" />
                                        </a>
                                    </article>
                                @endforeach
                            </div>
                        </section>
                    </div>
                @endif
                @break

            {{-- ================= طلب عرض السعر ================= --}}
            @case('cta')
                <x-site.cta
                    :title="$block->heading()"
                    :description="$block->intro()"
                    action="اطلب عرض سعر"
                    message="السلام عليكم، أرغب في عرض سعر لتصوير فعالية"
                    compact
                />
                @break

        @endswitch
    @endforeach
</div>
