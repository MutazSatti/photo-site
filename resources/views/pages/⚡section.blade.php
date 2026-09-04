<?php

use App\Models\Faq;
use App\Models\Post;
use App\Models\Section;
use App\Support\Schema;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    public Section $section;

    /**
     * Livewire يربط {section} بالنموذج تلقائيًا عبر getRouteKeyName() = slug،
     * فلا حاجة لاستعلام يدوي — يبقى التحقّق من أن القسم منشور.
     */
    public function mount(Section $section): void
    {
        abort_unless($section->is_active, 404);

        $this->section = $section->load('activeCategories');

        seo()
            ->set(
                title: $this->section->metaTitle(),
                description: $this->section->metaDescription(),
                type: 'website',
            )
            ->breadcrumbs([['label' => $section->name, 'url' => $section->url()]])
            ->addGraph(
                Schema::sectionPage($this->section, $this->posts->getCollection()),
                Schema::faqPage($this->faqs, $this->section->url()) ?? [],
            );
    }

    #[Computed]
    public function posts()
    {
        return Post::query()
            ->published()
            ->where('section_id', $this->section->id)
            ->with(['section:id,slug,name', 'category:id,slug,name,section_id', 'media'])
            ->ordered()
            ->paginate(12);
    }

    #[Computed]
    public function faqs()
    {
        return Faq::query()
            ->active()
            ->where('section_id', $this->section->id)
            ->ordered()
            ->get();
    }
}; ?>

<div class="sec-theme" style="{{ $section->colorStyle() }}">
    <x-site.page-header
        :title="$section->name"
        :tagline="$section->tagline"
        :description="$section->description"
        :icon="$section->icon"
        :breadcrumbs="[['label' => $section->name]]"
    >
        <x-slot:actions>
            <x-ui.button href="{{ route('contact') }}" variant="primary" icon="send">تواصل للحجز</x-ui.button>
            <x-ui.button href="{{ whatsapp_url('السلام عليكم، أرغب في الاستفسار عن ' . $section->name) }}" variant="outline" icon="whatsapp" :navigate="false" target="_blank" rel="noopener">
                واتساب
            </x-ui.button>
        </x-slot:actions>
    </x-site.page-header>

    <div class="px-4 py-12 mx-auto max-w-7xl sm:px-6 lg:px-8">

        {{-- ================= الأقسام الفرعية ================= --}}
        @if ($section->activeCategories->isNotEmpty())
            <section class="mb-14">
                <h2 class="text-xl font-extrabold text-ink-900 sm:text-2xl dark:text-ink-50">الأقسام الفرعية</h2>

                <div class="grid gap-5 mt-6 sm:grid-cols-2">
                    @foreach ($section->activeCategories as $category)
                        <a
                            href="{{ $category->url() }}"
                            wire:navigate
                            class="flex items-start gap-5 p-6 transition-all border group rounded-2xl border-ink-200 hover:sec-border hover:bg-ink-50 dark:border-ink-800  dark:hover:bg-ink-900"
                        >
                            <span class="flex items-center justify-center transition-colors shrink-0 size-12 rounded-2xl sec-bg-soft sec-text group-hover:sec-bg-solid group-hover:text-white">
                                <x-icon :name="$category->icon" :size="22" />
                            </span>

                            <div class="min-w-0">
                                <h3 class="text-base font-extrabold text-ink-900 dark:text-ink-50">{{ $category->name }}</h3>
                                <p class="mt-1 text-sm font-bold sec-text">{{ $category->tagline }}</p>
                                <p class="mt-2 text-sm leading-7 text-ink-600 line-clamp-2 dark:text-ink-400">{{ $category->description }}</p>
                            </div>
                        </a>
                    @endforeach
                </div>
            </section>
        @endif

        {{-- ================= العناصر ================= --}}
        <section>
            <div class="flex items-center justify-between gap-4">
                <h2 class="text-xl font-extrabold text-ink-900 sm:text-2xl dark:text-ink-50">
                    {{ $section->slug === App\Models\Section::SERVICES ? 'أحدث الأعمال' : $section->name }}
                </h2>
                <p class="text-sm text-ink-500 dark:text-ink-400">{{ $this->posts->total() }} عنصر</p>
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
                    :icon="$section->icon"
                    title="لم يُضف محتوى لهذا القسم بعد"
                    description="أضف أول عنصر من لوحة التحكم ليظهر هنا."
                />
            @endif
        </section>

        {{-- ================= أسئلة القسم ================= --}}
        @if ($this->faqs->isNotEmpty())
            <section class="mt-16">
                <h2 class="text-xl font-extrabold text-ink-900 sm:text-2xl dark:text-ink-50">أسئلة عن {{ $section->name }}</h2>

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
    </div>

    <x-site.cta :title="'احجز ' . $section->name" compact />
</div>
