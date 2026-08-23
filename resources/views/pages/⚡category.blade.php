<?php

use App\Models\Category;
use App\Models\Post;
use App\Models\Section;
use App\Support\Schema;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    public Category $category;

    public Section $section;

    /**
     * Livewire يربط {section} و{category} بنموذجيهما عبر الـ slug.
     * يبقى التأكّد من أن القسم الفرعي ينتمي فعلًا لهذا القسم الرئيسي،
     * وإلا لصار الرابط /articles/events صالحًا وهو غير موجود منطقيًا.
     */
    public function mount(Section $section, Category $category): void
    {
        abort_unless(
            $section->is_active && $category->is_active && $category->section_id === $section->id,
            404,
        );

        $this->section = $section;
        $this->category = $category;

        seo()
            ->set(
                title: $this->category->metaTitle(),
                description: $this->category->metaDescription(),
                image: $this->coverImageUrl(),
            )
            ->breadcrumbs([
                ['label' => $this->section->name, 'url' => $this->section->url()],
                ['label' => $this->category->name, 'url' => $this->category->url()],
            ])
            ->addGraph(
                Schema::servicePage($this->category),
                [
                    '@type' => 'CollectionPage',
                    '@id' => $this->category->url().'#page',
                    'url' => $this->category->url(),
                    'name' => $this->category->metaTitle(),
                    'description' => $this->category->metaDescription(),
                    'inLanguage' => 'ar',
                    'isPartOf' => ['@id' => Schema::websiteId()],
                    'mainEntity' => [
                        '@type' => 'ItemList',
                        'numberOfItems' => $this->posts->total(),
                        'itemListElement' => $this->posts->getCollection()->values()
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

    #[Computed]
    public function posts()
    {
        return Post::query()
            ->published()
            ->where('category_id', $this->category->id)
            ->with(['section:id,slug,name', 'category:id,slug,name,section_id', 'media'])
            ->ordered()
            ->paginate(12);
    }

    private function coverImageUrl(): ?string
    {
        return $this->posts->getCollection()->first()?->coverImage()?->url('lg');
    }
}; ?>

<div>
    <x-site.page-header
        :title="$category->name . ' في ' . config('site.location.city')"
        :tagline="$category->tagline"
        :description="$category->description"
        :icon="$category->icon"
        :breadcrumbs="[
            ['label' => $section->name, 'url' => $section->url()],
            ['label' => $category->name],
        ]"
    >
        <x-slot:actions>
            <x-ui.button href="{{ whatsapp_url('السلام عليكم، أرغب في الاستفسار عن خدمة ' . $category->name) }}" variant="whatsapp" icon="whatsapp" :navigate="false" target="_blank" rel="noopener">
                اطلب عرض سعر
            </x-ui.button>
            <x-ui.button href="{{ route('contact') }}" variant="outline" icon="send">نموذج الحجز</x-ui.button>
        </x-slot:actions>
    </x-site.page-header>

    <div class="px-4 py-12 mx-auto max-w-7xl sm:px-6 lg:px-8">
        <div class="flex items-center justify-between gap-4">
            <h2 class="text-xl font-extrabold text-ink-900 sm:text-2xl dark:text-ink-50">الأعمال</h2>
            <p class="text-sm text-ink-500 dark:text-ink-400">{{ $this->posts->total() }} عمل</p>
        </div>

        @if ($this->posts->isNotEmpty())
            <div class="grid gap-8 mt-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($this->posts as $post)
                    <x-site.post-card :post="$post" :eager="$loop->index < 3" />
                @endforeach
            </div>

            <div class="mt-12">{{ $this->posts->links() }}</div>
        @else
            <x-site.empty-state
                class="mt-6"
                :icon="$category->icon"
                title="لا توجد أعمال منشورة في هذا القسم بعد"
                description="أضف أعمالك من لوحة التحكم لتظهر هنا مع صورها."
            />
        @endif

        {{-- الأقسام الأخرى — تبقي الزائر داخل الموقع --}}
        @php
            $siblings = $section->activeCategories()->where('id', '!=', $category->id)->get();
        @endphp

        @if ($siblings->isNotEmpty())
            <section class="pt-10 mt-16 border-t border-ink-200 dark:border-ink-800">
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
        :title="'احجز جلسة ' . $category->name"
        :description="$category->description"
        compact
    />
</div>
