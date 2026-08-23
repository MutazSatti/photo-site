<?php

use App\Models\Category;
use App\Models\Post;
use App\Models\Section;
use App\Support\Schema;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    public Post $post;

    /**
     * يخدم مسارين: /{section}/{post} للأقسام بلا أقسام فرعية،
     * و /{section}/{category}/{post} للأعمال داخل خدمات التصوير.
     *
     * Livewire يربط كل معامل بنموذجه عبر الـ slug. التحقّق هنا يمنع الوصول
     * إلى عنصر عبر رابط لا يطابق قسمه الحقيقي أو إلى مسوّدة غير منشورة.
     */
    public function mount(Section $section, Post $post, ?Category $category = null): void
    {
        abort_unless($this->isVisible($post, $section, $category), 404);

        $this->post = $post->load(['section', 'category', 'media']);

        $this->countView();

        $cover = $this->post->coverImage();

        seo()
            ->set(
                title: $this->post->metaTitle(),
                description: $this->post->metaDescription(),
                image: $cover?->url('lg'),
                type: 'article',
            )
            ->breadcrumbs(array_values(array_filter([
                ['label' => $this->post->section->name, 'url' => $this->post->section->url()],
                $this->post->category
                    ? ['label' => $this->post->category->name, 'url' => $this->post->category->url()]
                    : null,
                ['label' => $this->post->title, 'url' => $this->post->url()],
            ])))
            ->addGraph(
                Schema::post($this->post),
                Schema::imageGallery($this->post) ?? [],
            );
    }

    /** العنصر منشور، وينتمي فعلًا للقسم (والقسم الفرعي) الواردين في الرابط. */
    private function isVisible(Post $post, Section $section, ?Category $category): bool
    {
        $isPublished = $post->status === 'published'
            && ($post->published_at === null || $post->published_at->isPast());

        return $isPublished
            && $post->section_id === $section->id
            && ($category === null || $post->category_id === $category->id);
    }

    /**
     * عدّاد مشاهدات بسيط — يزيد مرة واحدة لكل جلسة لكل عنصر
     * حتى لا يضخّمه تحديث الصفحة.
     */
    private function countView(): void
    {
        $key = 'viewed.post.'.$this->post->id;

        if (session()->has($key)) {
            return;
        }

        session()->put($key, true);

        Post::whereKey($this->post->id)->increment('views');
    }

    #[Computed]
    public function related()
    {
        return Post::query()
            ->published()
            ->whereKeyNot($this->post->id)
            ->where(fn ($q) => $q
                ->where('category_id', $this->post->category_id)
                ->orWhere('section_id', $this->post->section_id))
            ->with(['section:id,slug,name', 'category:id,slug,name,section_id', 'media'])
            ->ordered()
            ->take(3)
            ->get();
    }

    #[Computed]
    public function isWorkshop(): bool
    {
        return $this->post->section->slug === Section::WORKSHOPS;
    }
}; ?>

<div>
    <article>
        {{-- ================= ترويسة العنصر ================= --}}
        <header class="border-b border-ink-200 bg-ink-50 dark:border-ink-800 dark:bg-ink-900">
            <div class="px-4 py-10 mx-auto max-w-4xl sm:px-6 lg:px-8 lg:py-14">
                <x-site.breadcrumbs
                    class="mb-6"
                    :items="array_values(array_filter([
                        ['label' => $post->section->name, 'url' => $post->section->url()],
                        $post->category ? ['label' => $post->category->name, 'url' => $post->category->url()] : null,
                        ['label' => $post->title],
                    ]))"
                />

                <div class="flex flex-wrap items-center gap-2">
                    <x-ui.badge variant="brand">{{ $post->category->name ?? $post->section->name }}</x-ui.badge>

                    @if ($post->published_at)
                        <span class="inline-flex items-center gap-1.5 text-xs text-ink-500 dark:text-ink-400">
                            <x-icon name="calendar" :size="13" />
                            {{ $post->published_at->translatedFormat('j F Y') }}
                        </span>
                    @endif

                    @if (! $this->isWorkshop && $post->section->slug !== App\Models\Section::SERVICES)
                        <span class="inline-flex items-center gap-1.5 text-xs text-ink-500 dark:text-ink-400">
                            <x-icon name="clock" :size="13" />
                            {{ $post->readingTime() }} دقائق قراءة
                        </span>
                    @endif
                </div>

                <h1 class="mt-5 text-3xl font-extrabold leading-tight text-balance text-ink-900 sm:text-4xl dark:text-ink-50">
                    {{ $post->title }}
                </h1>

                @if ($post->subtitle)
                    <p class="mt-3 text-lg font-bold text-brand-600 dark:text-brand-400">{{ $post->subtitle }}</p>
                @endif

                @if ($post->excerpt)
                    <p class="mt-5 text-base leading-9 text-ink-600 dark:text-ink-400">{{ $post->excerpt }}</p>
                @endif

                {{-- تفاصيل الورشة أو العمل --}}
                @php
                    $facts = array_filter([
                        $post->location ? ['icon' => 'map-pin', 'label' => 'الموقع', 'value' => $post->location] : null,
                        $post->client ? ['icon' => 'users', 'label' => 'الجهة', 'value' => $post->client] : null,
                        $post->event_date ? ['icon' => 'calendar', 'label' => 'التاريخ', 'value' => $post->event_date->translatedFormat('j F Y')] : null,
                        $post->duration ? ['icon' => 'clock', 'label' => 'المدة', 'value' => $post->duration] : null,
                        $post->seats ? ['icon' => 'users', 'label' => 'المقاعد', 'value' => $post->seats . ' متدرّب'] : null,
                    ]);
                @endphp

                @if ($facts)
                    <dl class="grid gap-4 pt-6 mt-8 border-t sm:grid-cols-2 lg:grid-cols-3 border-ink-200 dark:border-ink-800">
                        @foreach ($facts as $fact)
                            <div class="flex items-start gap-3">
                                <span class="mt-0.5 flex size-8 shrink-0 items-center justify-center rounded-lg bg-white text-ink-500 dark:bg-ink-800 dark:text-ink-400">
                                    <x-icon :name="$fact['icon']" :size="15" />
                                </span>
                                <div>
                                    <dt class="text-xs text-ink-500 dark:text-ink-400">{{ $fact['label'] }}</dt>
                                    <dd class="text-sm font-bold text-ink-900 dark:text-ink-100">{{ $fact['value'] }}</dd>
                                </div>
                            </div>
                        @endforeach
                    </dl>
                @endif

                @if ($this->isWorkshop)
                    <div class="flex flex-wrap items-center gap-4 p-5 mt-8 bg-white border rounded-2xl border-brand-200 dark:border-brand-900 dark:bg-ink-950">
                        @if ($post->price)
                            <div>
                                <p class="text-xs text-ink-500 dark:text-ink-400">رسوم الاشتراك</p>
                                <p class="text-2xl font-extrabold text-brand-600 dark:text-brand-400">@money($post->price)</p>
                            </div>
                        @endif

                        <x-ui.button
                            href="{{ whatsapp_url('السلام عليكم، أرغب في التسجيل في ' . $post->title) }}"
                            variant="whatsapp"
                            size="lg"
                            icon="whatsapp"
                            :navigate="false"
                            target="_blank"
                            rel="noopener"
                            class="ms-auto"
                        >
                            سجّل في الورشة
                        </x-ui.button>
                    </div>
                @endif
            </div>
        </header>

        {{-- ================= معرض الصور ================= --}}
        @if ($post->media->isNotEmpty())
            <section class="px-4 py-12 mx-auto max-w-7xl sm:px-6 lg:px-8">
                <h2 class="sr-only">صور {{ $post->title }}</h2>
                <x-site.gallery :media="$post->media" :columns="3" />
            </section>
        @endif

        {{-- ================= المحتوى ================= --}}
        @if ($post->body)
            <section class="px-4 pb-12 mx-auto max-w-3xl sm:px-6 lg:px-8">
                <div class="prose-ar">
                    {!! $post->body !!}
                </div>
            </section>
        @endif

        {{-- ================= التذييل ================= --}}
        <footer class="px-4 pb-12 mx-auto max-w-3xl sm:px-6 lg:px-8">
            <div class="flex flex-wrap items-center justify-between gap-4 pt-6 border-t border-ink-200 dark:border-ink-800">
                <div class="flex items-center gap-3">
                    <span class="flex items-center justify-center rounded-full size-10 bg-brand-500 text-ink-950">
                        <x-icon name="aperture" :size="18" />
                    </span>
                    <div>
                        <p class="text-sm font-extrabold text-ink-900 dark:text-ink-100">{{ config('site.owner_name') }}</p>
                        <p class="text-xs text-ink-500 dark:text-ink-400">{{ config('site.job_title') }} — {{ config('site.location.city') }}</p>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <span class="inline-flex items-center gap-1.5 text-xs text-ink-400">
                        <x-icon name="eye" :size="13" />
                        {{ number_format($post->views) }}
                    </span>
                </div>
            </div>
        </footer>
    </article>

    {{-- ================= أعمال ذات صلة ================= --}}
    @if ($this->related->isNotEmpty())
        <section class="border-t border-ink-200 bg-ink-50 dark:border-ink-800 dark:bg-ink-900">
            <div class="px-4 py-14 mx-auto max-w-7xl sm:px-6 lg:px-8">
                <h2 class="text-xl font-extrabold text-ink-900 sm:text-2xl dark:text-ink-50">أعمال ذات صلة</h2>

                <div class="grid gap-8 mt-8 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($this->related as $item)
                        <x-site.post-card :post="$item" />
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    <x-site.cta compact />
</div>
