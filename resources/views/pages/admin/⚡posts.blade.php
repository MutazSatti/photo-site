<?php

use App\Models\Post;
use App\Models\Section;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts::admin', ['title' => 'الأعمال والمحتوى'])] class extends Component
{
    use WithPagination;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(as: 'section', except: '')]
    public string $sectionSlug = '';

    #[Url(as: 'status', except: '')]
    public string $status = '';

    public ?int $confirmingDelete = null;

    public function updated(string $property): void
    {
        if (in_array($property, ['search', 'sectionSlug', 'status'], true)) {
            $this->resetPage();
        }
    }

    #[Computed]
    public function sections()
    {
        return Section::query()->active()->ordered()->get();
    }

    #[Computed]
    public function posts()
    {
        return Post::query()
            ->with(['section:id,name,slug', 'category:id,name,slug,section_id', 'media'])
            ->when($this->search, fn ($q) => $q->where(fn ($sub) => $sub
                ->where('title', 'like', '%'.$this->search.'%')
                ->orWhere('excerpt', 'like', '%'.$this->search.'%')))
            ->when($this->sectionSlug, fn ($q) => $q->whereHas('section', fn ($s) => $s->where('slug', $this->sectionSlug)))
            ->when($this->status, fn ($q) => $q->where('status', $this->status))
            ->orderByDesc('updated_at')
            ->paginate(15);
    }

    /** النشر والإخفاء بضغطة واحدة من القائمة دون فتح صفحة التحرير. */
    public function toggleStatus(int $id): void
    {
        $post = Post::findOrFail($id);

        $post->update([
            'status' => $post->status === 'published' ? 'draft' : 'published',
            'published_at' => $post->published_at ?? now(),
        ]);

        $this->flushCaches();

        $this->dispatch('notify', message: $post->status === 'published' ? 'نُشر العنصر.' : 'أُخفي العنصر.');
    }

    public function toggleFeatured(int $id): void
    {
        $post = Post::findOrFail($id);

        $post->update(['is_featured' => ! $post->is_featured]);

        $this->flushCaches();

        $this->dispatch('notify', message: $post->is_featured ? 'أُضيف إلى المميّزة.' : 'أُزيل من المميّزة.');
    }

    public function delete(int $id): void
    {
        // حذف العنصر يحذف صوره من القرص عبر حدث deleting في نموذج Media
        Post::findOrFail($id)->delete();

        $this->confirmingDelete = null;
        $this->flushCaches();

        $this->dispatch('notify', message: 'حُذف العنصر وصوره.');
    }

    /** أي تغيير في المحتوى يبطل خرائط الموقع وحمولة المزامنة. */
    private function flushCaches(): void
    {
        cache()->forget('sync.payload');
        cache()->forget('sync.manifest');
        cache()->forget('feed.sitemap');
        cache()->forget('feed.llms');
    }
}; ?>

<div>
    <x-admin.page-header
        title="الأعمال والمحتوى"
        description="كل ما يظهر في المعرض: الأعمال المصوّرة، الورش، المقالات، والمنشورات التعليمية."
    >
        <x-slot:actions>
            <x-ui.button href="{{ route('admin.posts.create') }}" icon="plus">إضافة عنصر</x-ui.button>
        </x-slot:actions>
    </x-admin.page-header>

    {{-- ================= التصفية ================= --}}
    <div class="flex flex-col gap-3 mb-5 sm:flex-row">
        <div class="relative grow">
            <span class="absolute -translate-y-1/2 pointer-events-none start-3.5 top-1/2 text-ink-400">
                <x-icon name="search" :size="16" />
            </span>
            <input
                type="search"
                wire:model.live.debounce.400ms="search"
                placeholder="ابحث بالعنوان أو الملخّص…"
                class="w-full py-2.5 text-sm bg-white border rounded-xl border-ink-300 ps-10 pe-4 text-ink-900 placeholder:text-ink-400 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/40 dark:border-ink-700 dark:bg-ink-900 dark:text-ink-100"
                aria-label="بحث"
            >
        </div>

        <x-ui.select wire:model.live="sectionSlug" class="sm:w-48" aria-label="تصفية بالقسم">
            <option value="">كل الأقسام</option>
            @foreach ($this->sections as $section)
                <option value="{{ $section->slug }}">{{ $section->name }}</option>
            @endforeach
        </x-ui.select>

        <x-ui.select wire:model.live="status" class="sm:w-40" aria-label="تصفية بالحالة">
            <option value="">كل الحالات</option>
            <option value="published">منشور</option>
            <option value="draft">مسوّدة</option>
        </x-ui.select>
    </div>

    {{-- ================= القائمة ================= --}}
    <x-admin.card :padded="false">
        @if ($this->posts->isNotEmpty())
            <ul class="divide-y divide-ink-200 dark:divide-ink-800">
                @foreach ($this->posts as $post)
                    <li class="p-4 sm:px-5">
                        <div class="flex flex-wrap items-center gap-4">
                            <div class="overflow-hidden rounded-xl size-14 shrink-0 bg-ink-100 dark:bg-ink-800">
                                <x-site.picture :media="$post->coverImage()" variant="thumb" class="size-full" />
                            </div>

                            <div class="min-w-0 grow basis-60">
                                <div class="flex flex-wrap items-center gap-2">
                                    <p class="text-sm font-extrabold truncate text-ink-900 dark:text-ink-100">{{ $post->title }}</p>

                                    @if ($post->is_featured)
                                        <x-ui.badge variant="brand" icon="star">مميّز</x-ui.badge>
                                    @endif
                                </div>

                                <p class="mt-1 text-xs text-ink-500 dark:text-ink-400">
                                    {{ $post->section->name ?? '—' }}
                                    @if ($post->category) · {{ $post->category->name }} @endif
                                    · {{ $post->media->count() }} صورة
                                    · {{ number_format($post->views) }} مشاهدة
                                </p>
                            </div>

                            <div class="flex items-center gap-1.5 ms-auto">
                                <button
                                    type="button"
                                    wire:click="toggleStatus({{ $post->id }})"
                                    class="rounded-lg px-2.5 py-1 text-xs font-bold transition-colors {{ $post->status === 'published'
                                        ? 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100 dark:bg-emerald-950 dark:text-emerald-400'
                                        : 'bg-ink-100 text-ink-600 hover:bg-ink-200 dark:bg-ink-800 dark:text-ink-400' }}"
                                    title="{{ $post->status === 'published' ? 'إخفاء' : 'نشر' }}"
                                >
                                    {{ $post->status === 'published' ? 'منشور' : 'مسوّدة' }}
                                </button>

                                <button
                                    type="button"
                                    wire:click="toggleFeatured({{ $post->id }})"
                                    class="rounded-lg p-2 transition-colors {{ $post->is_featured ? 'text-brand-500' : 'text-ink-400' }} hover:bg-ink-100 dark:hover:bg-ink-800"
                                    aria-label="تبديل التمييز"
                                >
                                    <x-icon :name="$post->is_featured ? 'star-filled' : 'star'" :size="16" />
                                </button>

                                <a
                                    href="{{ $post->url() }}"
                                    target="_blank"
                                    rel="noopener"
                                    class="p-2 transition-colors rounded-lg text-ink-500 hover:bg-ink-100 dark:hover:bg-ink-800"
                                    aria-label="معاينة"
                                >
                                    <x-icon name="eye" :size="16" />
                                </a>

                                <a
                                    href="{{ route('admin.posts.edit', $post) }}"
                                    wire:navigate
                                    class="p-2 transition-colors rounded-lg text-ink-500 hover:bg-ink-100 dark:hover:bg-ink-800"
                                    aria-label="تعديل"
                                >
                                    <x-icon name="pencil" :size="16" />
                                </a>

                                <button
                                    type="button"
                                    wire:click="$set('confirmingDelete', {{ $post->id }})"
                                    class="p-2 text-red-600 transition-colors rounded-lg hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-950/40"
                                    aria-label="حذف"
                                >
                                    <x-icon name="trash" :size="16" />
                                </button>
                            </div>
                        </div>

                        @if ($confirmingDelete === $post->id)
                            <div class="flex flex-wrap items-center gap-3 p-3 mt-3 border border-red-200 rounded-xl bg-red-50 dark:border-red-900 dark:bg-red-950/40">
                                <p class="text-sm font-bold text-red-800 dark:text-red-300">
                                    حذف "{{ $post->title }}" نهائيًا مع {{ $post->media->count() }} صورة؟ لا يمكن التراجع.
                                </p>
                                <div class="flex gap-2 ms-auto">
                                    <x-ui.button wire:click="delete({{ $post->id }})" variant="danger" size="sm" icon="trash">
                                        نعم، احذف
                                    </x-ui.button>
                                    <x-ui.button wire:click="$set('confirmingDelete', null)" variant="ghost" size="sm">
                                        تراجع
                                    </x-ui.button>
                                </div>
                            </div>
                        @endif
                    </li>
                @endforeach
            </ul>

            @if ($this->posts->hasPages())
                <div class="px-5 py-4 border-t border-ink-200 dark:border-ink-800">
                    {{ $this->posts->links() }}
                </div>
            @endif
        @else
            <div class="px-5 py-16 text-center">
                <p class="text-sm text-ink-500 dark:text-ink-400">لا توجد عناصر مطابقة.</p>
                <x-ui.button href="{{ route('admin.posts.create') }}" icon="plus" class="mt-4">إضافة أول عنصر</x-ui.button>
            </div>
        @endif
    </x-admin.card>
</div>
