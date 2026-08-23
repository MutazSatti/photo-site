<?php

use App\Models\Category;
use App\Models\Post;
use App\Models\Section;
use App\Support\Schema;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(as: 'section', except: '')]
    public string $sectionSlug = '';

    #[Url(as: 'category', except: '')]
    public string $categorySlug = '';

    public function mount(): void
    {
        $city = config('site.location.city');

        seo()
            ->set(
                title: 'معرض الأعمال',
                description: "معرض أعمال التصوير الفوتوغرافي في {$city}: تغطيات المناسبات والفعاليات والمؤتمرات والمعارض والتصوير العقاري، إضافة إلى الورش التدريبية والمقالات.",
            )
            ->breadcrumbs([['label' => 'المعرض', 'url' => route('portfolio')]])
            ->addGraph([
                '@type' => 'CollectionPage',
                '@id' => route('portfolio').'#page',
                'url' => route('portfolio'),
                'name' => 'معرض الأعمال',
                'inLanguage' => 'ar',
                'isPartOf' => ['@id' => Schema::websiteId()],
                'about' => ['@id' => Schema::businessId()],
            ]);
    }

    /** أي تغيير في الفلترة يعيد الترقيم إلى الصفحة الأولى. */
    public function updated(string $property): void
    {
        if (in_array($property, ['search', 'sectionSlug', 'categorySlug'], true)) {
            $this->resetPage();
        }

        // اختيار قسم مختلف يُسقط القسم الفرعي لأنه لم يعد ينتمي إليه
        if ($property === 'sectionSlug') {
            $this->categorySlug = '';
        }
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'sectionSlug', 'categorySlug']);
        $this->resetPage();
    }

    #[Computed]
    public function sections()
    {
        return Section::query()->active()->ordered()->get();
    }

    #[Computed]
    public function categories()
    {
        if ($this->sectionSlug !== Section::SERVICES) {
            return collect();
        }

        return Category::query()
            ->active()
            ->ordered()
            ->whereHas('section', fn ($q) => $q->where('slug', Section::SERVICES))
            ->get();
    }

    #[Computed]
    public function posts()
    {
        return Post::query()
            ->published()
            ->with(['section:id,slug,name', 'category:id,slug,name,section_id', 'media'])
            ->when($this->sectionSlug, fn ($q) => $q->whereHas('section', fn ($s) => $s->where('slug', $this->sectionSlug)))
            ->when($this->categorySlug, fn ($q) => $q->whereHas('category', fn ($c) => $c->where('slug', $this->categorySlug)))
            ->when($this->search, function ($q) {
                $term = '%'.$this->search.'%';

                $q->where(fn ($sub) => $sub
                    ->where('title', 'like', $term)
                    ->orWhere('excerpt', 'like', $term)
                    ->orWhere('body', 'like', $term)
                    ->orWhere('location', 'like', $term));
            })
            ->ordered()
            ->paginate(12);
    }

    #[Computed]
    public function hasFilters(): bool
    {
        return $this->search !== '' || $this->sectionSlug !== '' || $this->categorySlug !== '';
    }
}; ?>

<div>
    <x-site.page-header
        title="معرض الأعمال"
        tagline="كل ما صوّرته في مكان واحد"
        description="تغطيات المناسبات والفعاليات والمعارض والعقارات، والورش التدريبية، والمقالات والمنشورات التعليمية."
        icon="images"
        :breadcrumbs="[['label' => 'المعرض']]"
    />

    <div class="px-4 py-12 mx-auto max-w-7xl sm:px-6 lg:px-8">

        {{-- ================= أدوات التصفية ================= --}}
        <div class="flex flex-col gap-4">
            <div class="relative">
                <span class="absolute -translate-y-1/2 pointer-events-none start-4 top-1/2 text-ink-400">
                    <x-icon name="search" :size="18" />
                </span>

                <input
                    type="search"
                    wire:model.live.debounce.400ms="search"
                    placeholder="ابحث في العناوين والتفاصيل والمواقع…"
                    class="w-full py-3 text-sm transition-colors bg-white border rounded-xl border-ink-300 ps-12 pe-4 text-ink-900 placeholder:text-ink-400 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/40 dark:border-ink-700 dark:bg-ink-900 dark:text-ink-100"
                    aria-label="البحث في المعرض"
                >

                <div wire:loading wire:target="search" class="absolute -translate-y-1/2 end-4 top-1/2 text-ink-400">
                    <span class="block animate-spin"><x-icon name="refresh" :size="16" /></span>
                </div>
            </div>

            <div class="flex flex-wrap gap-2">
                <button
                    type="button"
                    wire:click="$set('sectionSlug', '')"
                    class="rounded-full px-4 py-2 text-sm font-bold transition-colors {{ $sectionSlug === '' ? 'bg-ink-900 text-white dark:bg-brand-500 dark:text-ink-950' : 'border border-ink-300 text-ink-700 hover:border-ink-400 dark:border-ink-700 dark:text-ink-300' }}"
                >
                    الكل
                </button>

                @foreach ($this->sections as $section)
                    <button
                        type="button"
                        wire:click="$set('sectionSlug', '{{ $section->slug }}')"
                        class="inline-flex items-center gap-2 rounded-full px-4 py-2 text-sm font-bold transition-colors {{ $sectionSlug === $section->slug ? 'bg-ink-900 text-white dark:bg-brand-500 dark:text-ink-950' : 'border border-ink-300 text-ink-700 hover:border-ink-400 dark:border-ink-700 dark:text-ink-300' }}"
                    >
                        <x-icon :name="$section->icon" :size="15" />
                        {{ $section->name }}
                    </button>
                @endforeach
            </div>

            @if ($this->categories->isNotEmpty())
                <div class="flex flex-wrap gap-2 pt-1 border-t ps-1 border-ink-200 dark:border-ink-800">
                    <span class="py-2 text-xs font-bold text-ink-500 dark:text-ink-400">القسم الفرعي:</span>

                    <button
                        type="button"
                        wire:click="$set('categorySlug', '')"
                        class="rounded-full px-3 py-1.5 text-xs font-bold transition-colors {{ $categorySlug === '' ? 'bg-brand-500 text-ink-950' : 'border border-ink-300 text-ink-600 dark:border-ink-700 dark:text-ink-400' }}"
                    >
                        الكل
                    </button>

                    @foreach ($this->categories as $category)
                        <button
                            type="button"
                            wire:click="$set('categorySlug', '{{ $category->slug }}')"
                            class="rounded-full px-3 py-1.5 text-xs font-bold transition-colors {{ $categorySlug === $category->slug ? 'bg-brand-500 text-ink-950' : 'border border-ink-300 text-ink-600 hover:border-ink-400 dark:border-ink-700 dark:text-ink-400' }}"
                        >
                            {{ $category->name }}
                        </button>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- ================= النتائج ================= --}}
        <div class="flex items-center justify-between gap-4 mt-8">
            <p class="text-sm text-ink-500 dark:text-ink-400">
                {{ $this->posts->total() }} عنصر
                @if ($this->hasFilters)
                    <span class="text-ink-400">— بعد التصفية</span>
                @endif
            </p>

            @if ($this->hasFilters)
                <x-ui.button wire:click="clearFilters" variant="ghost" size="sm" icon="close">
                    إزالة التصفية
                </x-ui.button>
            @endif
        </div>

        @if ($this->posts->isNotEmpty())
            <div class="grid gap-8 mt-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($this->posts as $post)
                    <x-site.post-card :post="$post" :eager="$loop->index < 3" />
                @endforeach
            </div>

            <div class="mt-12">
                {{ $this->posts->links() }}
            </div>
        @else
            <x-site.empty-state
                class="mt-6"
                icon="search"
                title="لا توجد نتائج مطابقة"
                description="جرّب كلمة بحث أخرى أو أزل التصفية لعرض كل الأعمال."
            >
                <x-slot:actions>
                    <x-ui.button wire:click="clearFilters" variant="outline" icon="refresh">
                        عرض كل الأعمال
                    </x-ui.button>
                </x-slot:actions>
            </x-site.empty-state>
        @endif
    </div>

    <x-site.cta compact />
</div>
