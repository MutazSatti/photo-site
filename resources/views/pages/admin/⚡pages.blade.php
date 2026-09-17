<?php

use App\Models\Category;
use App\Models\PageBlock;
use App\Models\PageBlockItem;
use App\Models\Post;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * تحرير صفحات الموقع المصمَّمة: نصوصها وترتيب أقسامها وظهورها.
 *
 * الصفحة المصمَّمة ليست قائمة أعمال، فنصّها جزء من التصميم لا من المحتوى. وكتابته
 * في القالب تعني أن كل جملة تحتاج مبرمجًا. هذه الشاشة تنقل القرار كاملًا إلى
 * المالك: العنوان والفقرة والمراحل والأرقام، وأيّ قسم يظهر وأين.
 *
 * وما لا يُحرَّر هنا له مكانه المعروف: الصور من «صور الواجهة»، والمجموعات وصورها
 * من «الأعمال والمحتوى»، والأسئلة من «الأسئلة الشائعة». والبطاقة تشير إلى مكان
 * كلٍّ منها بدل أن تكرّره.
 */
new #[Layout('layouts::admin', ['title' => 'محتوى الصفحات'])] class extends Component
{
    public string $page = PageBlock::EVENTS;

    /**
     * نصوص الأقسام، مفهرسة بالمعرّف.
     *
     * تُحمَّل من القاعدة لا من التعريفات: الحقل الفارغ هنا يعني «استعمل
     * المبدئي»، فلو ملأناه بالمبدئي لصار كل قسم محرَّرًا بمجرد فتح الصفحة
     * وانقطع عن أي تحسين لاحق في نصوص الشيفرة.
     *
     * @var array<int, array{title: string, subtitle: string, body: string}>
     */
    public array $blockText = [];

    /** @var array<int, array{icon: string, title: string, subtitle: string, body: string}> */
    public array $itemText = [];

    /** القسم الذي يُضاف إليه عنصر جديد، والعنصر الأب إن كان العنصر نقطة. */
    public ?int $addingToBlock = null;

    public ?int $addingToItem = null;

    /** @var array{icon: string, title: string, subtitle: string, body: string} */
    public array $newItem = ['icon' => '', 'title' => '', 'subtitle' => '', 'body' => ''];

    /** @var array<int, string> المجموعة المختارة لكل قسم معرض */
    public array $newGroup = [];

    public function mount(): void
    {
        $this->load();
    }

    /**
     * @return Collection<int, PageBlock>
     */
    #[Computed]
    public function blocks(): Collection
    {
        return PageBlock::query()
            ->onPage($this->page)
            ->ordered()
            ->with([
                'items' => fn ($q) => $q->orderBy('sort_order')->orderBy('id'),
                'items.children',
                'items.post:id,title,slug',
            ])
            ->get();
    }

    /** يملأ حقول النماذج بما في القاعدة. */
    public function load(): void
    {
        $this->blockText = [];
        $this->itemText = [];

        foreach ($this->blocks as $block) {
            $this->blockText[$block->id] = [
                'title' => (string) $block->title,
                'subtitle' => (string) $block->subtitle,
                'body' => (string) $block->body,
            ];

            foreach ($block->items as $item) {
                $this->fillItem($item);

                foreach ($item->children as $child) {
                    $this->fillItem($child);
                }
            }
        }
    }

    private function fillItem(PageBlockItem $item): void
    {
        $this->itemText[$item->id] = [
            'icon' => (string) $item->icon,
            'title' => (string) $item->title,
            'subtitle' => (string) $item->subtitle,
            'body' => (string) $item->body,
        ];
    }

    /** الحقول التي يعرضها القسم، بأسمائها كما عرّفها. */
    public function fieldsOf(PageBlock $block): array
    {
        $definition = PageBlock::definitionFor($this->page, $block->key);

        return $definition['fields'] ?? ['title' => 'العنوان', 'subtitle' => 'السطر تحته'];
    }

    /** حقول العنصر المتكرّر في هذا القسم — فارغة تعني أن القسم بلا عناصر. */
    public function itemFieldsOf(PageBlock $block): array
    {
        return PageBlock::definitionFor($this->page, $block->key)['item_fields'] ?? [];
    }

    public function childFieldsOf(PageBlock $block): array
    {
        return PageBlock::definitionFor($this->page, $block->key)['child_fields'] ?? [];
    }

    public function itemLabelOf(PageBlock $block): string
    {
        return PageBlock::definitionFor($this->page, $block->key)['item_label'] ?? 'عنصر';
    }

    public function childLabelOf(PageBlock $block): string
    {
        return PageBlock::definitionFor($this->page, $block->key)['child_label'] ?? 'نقطة';
    }

    /**
     * المجموعات التي يمكن إضافتها إلى قسم معرض: ما لم يُضف منها بعد.
     *
     * @return Collection<int, Post>
     */
    public function addableGroups(PageBlock $block): Collection
    {
        $source = $block->sourceCategory();

        if ($source === null) {
            return new Collection;
        }

        $category = Category::where('slug', $source)->first();

        if (! $category) {
            return new Collection;
        }

        $chosen = $block->items->pluck('post_id')->filter()->all();

        return Post::query()
            ->where('category_id', $category->id)
            ->whereNotIn('id', $chosen)
            ->orderBy('sort_order')
            ->get(['id', 'title', 'status']);
    }

    /*
    |--------------------------------------------------------------------------
    | الأقسام
    |--------------------------------------------------------------------------
    */

    /**
     * ينقل قسمًا خطوة، ثم يعيد ترقيم الجميع من الصفر.
     *
     * القسم المثبّت لا يتحرّك ولا يُتخطّى: الواجهة يجب أن تبقى أوّل ما يراه
     * الزائر، وإتاحة نقلها تحت أقسام أخرى تُنتج صفحة بلا مدخل.
     */
    public function moveBlock(int $id, string $direction): void
    {
        $blocks = $this->blocks->values();
        $from = $blocks->search(fn (PageBlock $b) => $b->id === $id);

        if ($from === false || $blocks[$from]->is_locked) {
            return;
        }

        $to = $direction === 'up' ? $from - 1 : $from + 1;

        if ($to < 0 || $to >= $blocks->count() || $blocks[$to]->is_locked) {
            return;
        }

        $reordered = $blocks->all();
        [$reordered[$from], $reordered[$to]] = [$reordered[$to], $reordered[$from]];

        foreach ($reordered as $position => $block) {
            if ($block->sort_order !== $position) {
                $block->update(['sort_order' => $position]);
            }
        }

        unset($this->blocks);
    }

    public function toggleBlock(int $id): void
    {
        $block = PageBlock::findOrFail($id);

        if ($block->is_locked) {
            return;
        }

        $block->update(['is_active' => ! $block->is_active]);

        unset($this->blocks);
    }

    public function saveBlockText(int $id): void
    {
        $this->validate([
            'blockText.'.$id.'.title' => ['nullable', 'string', 'max:150'],
            'blockText.'.$id.'.subtitle' => ['nullable', 'string', 'max:500'],
            'blockText.'.$id.'.body' => ['nullable', 'string', 'max:2000'],
        ], [], [
            'blockText.'.$id.'.title' => 'العنوان',
            'blockText.'.$id.'.subtitle' => 'السطر',
            'blockText.'.$id.'.body' => 'الفقرة',
        ]);

        // الفراغ يُحفظ null لا سلسلة فارغة، فيقرأه النموذج «أعد المبدئي»
        PageBlock::findOrFail($id)->update([
            'title' => trim($this->blockText[$id]['title'] ?? '') ?: null,
            'subtitle' => trim($this->blockText[$id]['subtitle'] ?? '') ?: null,
            'body' => trim($this->blockText[$id]['body'] ?? '') ?: null,
        ]);

        unset($this->blocks);
        $this->load();

        $this->dispatch('notify', message: 'حُدّث نصّ القسم.');
    }

    /*
    |--------------------------------------------------------------------------
    | العناصر
    |--------------------------------------------------------------------------
    */

    public function saveItem(int $id): void
    {
        $this->validate([
            'itemText.'.$id.'.icon' => ['nullable', 'string', 'in:'.implode(',', array_keys(PageBlockItem::icons()))],
            'itemText.'.$id.'.title' => ['nullable', 'string', 'max:150'],
            'itemText.'.$id.'.subtitle' => ['nullable', 'string', 'max:500'],
            'itemText.'.$id.'.body' => ['nullable', 'string', 'max:2000'],
        ], [], [
            'itemText.'.$id.'.title' => 'العنوان',
            'itemText.'.$id.'.subtitle' => 'السطر',
            'itemText.'.$id.'.body' => 'الفقرة',
        ]);

        PageBlockItem::findOrFail($id)->update([
            'icon' => trim($this->itemText[$id]['icon'] ?? '') ?: null,
            'title' => trim($this->itemText[$id]['title'] ?? '') ?: null,
            'subtitle' => trim($this->itemText[$id]['subtitle'] ?? '') ?: null,
            'body' => trim($this->itemText[$id]['body'] ?? '') ?: null,
        ]);

        unset($this->blocks);
        $this->load();

        $this->dispatch('notify', message: 'حُفظ العنصر.');
    }

    public function toggleItem(int $id): void
    {
        $item = PageBlockItem::findOrFail($id);
        $item->update(['is_active' => ! $item->is_active]);

        unset($this->blocks);
    }

    public function deleteItem(int $id): void
    {
        PageBlockItem::findOrFail($id)->delete();

        unset($this->blocks);
        $this->load();

        $this->dispatch('notify', message: 'حُذف العنصر.');
    }

    /** ينقل عنصرًا بين إخوته — الترتيب داخل الأب لا في الجدول كله. */
    public function moveItem(int $id, string $direction): void
    {
        $item = PageBlockItem::findOrFail($id);

        $siblings = PageBlockItem::query()
            ->where('page_block_id', $item->page_block_id)
            ->where(fn ($q) => $item->parent_id === null
                ? $q->whereNull('parent_id')
                : $q->where('parent_id', $item->parent_id))
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->values();

        $from = $siblings->search(fn (PageBlockItem $i) => $i->id === $id);
        $to = $direction === 'up' ? $from - 1 : $from + 1;

        if ($from === false || $to < 0 || $to >= $siblings->count()) {
            return;
        }

        $reordered = $siblings->all();
        [$reordered[$from], $reordered[$to]] = [$reordered[$to], $reordered[$from]];

        foreach ($reordered as $position => $sibling) {
            if ($sibling->sort_order !== $position) {
                $sibling->update(['sort_order' => $position]);
            }
        }

        unset($this->blocks);
    }

    public function startItem(int $blockId, ?int $parentId = null): void
    {
        $this->addingToBlock = $blockId;
        $this->addingToItem = $parentId;
        $this->newItem = ['icon' => '', 'title' => '', 'subtitle' => '', 'body' => ''];
        $this->resetErrorBag();

        $this->dispatch('page-form-opened');
    }

    public function cancelItem(): void
    {
        $this->addingToBlock = null;
        $this->addingToItem = null;
        $this->resetErrorBag();
    }

    public function addItem(): void
    {
        if ($this->addingToBlock === null) {
            return;
        }

        $this->validate([
            'newItem.icon' => ['nullable', 'string', 'in:'.implode(',', array_keys(PageBlockItem::icons()))],
            'newItem.title' => ['required', 'string', 'max:150'],
            'newItem.subtitle' => ['nullable', 'string', 'max:500'],
            'newItem.body' => ['nullable', 'string', 'max:2000'],
        ], [], [
            'newItem.title' => 'العنوان',
            'newItem.subtitle' => 'السطر',
            'newItem.body' => 'الفقرة',
        ]);

        $block = PageBlock::findOrFail($this->addingToBlock);

        $block->allItems()->create([
            'parent_id' => $this->addingToItem,
            'icon' => trim($this->newItem['icon']) ?: null,
            'title' => trim($this->newItem['title']),
            'subtitle' => trim($this->newItem['subtitle']) ?: null,
            'body' => trim($this->newItem['body']) ?: null,
            'sort_order' => $this->nextOrder($block->id, $this->addingToItem),
            'is_active' => true,
        ]);

        $this->cancelItem();
        unset($this->blocks);
        $this->load();

        $this->dispatch('notify', message: 'أُضيف العنصر.');
    }

    /** يضيف مجموعة أعمال إلى قسم معرض. */
    public function addGroup(int $blockId): void
    {
        $postId = (int) ($this->newGroup[$blockId] ?? 0);

        if ($postId === 0) {
            return;
        }

        $block = PageBlock::findOrFail($blockId);

        $this->validate([
            'newGroup.'.$blockId => ['required', 'exists:posts,id'],
        ], [], ['newGroup.'.$blockId => 'المجموعة']);

        $block->allItems()->create([
            'post_id' => $postId,
            'sort_order' => $this->nextOrder($block->id, null),
            'is_active' => true,
        ]);

        $this->newGroup[$blockId] = '';
        unset($this->blocks);
        $this->load();

        $this->dispatch('notify', message: 'أُضيفت المجموعة.');
    }

    private function nextOrder(int $blockId, ?int $parentId): int
    {
        return (int) PageBlockItem::query()
            ->where('page_block_id', $blockId)
            ->where(fn ($q) => $parentId === null ? $q->whereNull('parent_id') : $q->where('parent_id', $parentId))
            ->max('sort_order') + 1;
    }
}; ?>

<div>
    <x-admin.page-header
        title="محتوى الصفحات"
        description="نصوص الصفحات المصمَّمة وترتيب أقسامها. الحقل الفارغ يعود إلى نصّه الأصلي، وإخفاء القسم له زرّه."
    >
        <x-slot:actions>
            <x-ui.button :href="route('services.events')" variant="outline" icon="external-link" :navigate="false" target="_blank">
                عرض الصفحة
            </x-ui.button>
        </x-slot:actions>
    </x-admin.page-header>

    <div class="space-y-4">
        @foreach ($this->blocks as $index => $block)
            @php($fields = $this->fieldsOf($block))
            @php($itemFields = $this->itemFieldsOf($block))
            @php($childFields = $this->childFieldsOf($block))
            @php($source = $block->sourceCategory())

            <x-admin.card x-data="{ open: false }" :padded="false">
                {{-- ترويسة القسم: اسمه وحالته وأزرار ترتيبه --}}
                <header class="flex flex-wrap items-start justify-between gap-3 px-5 py-4">
                    <div class="min-w-0">
                        <div class="flex items-center gap-2">
                            <h2 class="text-sm font-extrabold text-ink-900 dark:text-ink-100">{{ $block->label }}</h2>

                            @if ($block->is_locked)
                                <x-ui.badge>مثبّت</x-ui.badge>
                            @elseif (! $block->is_active)
                                <x-ui.badge variant="warning">مخفيّ</x-ui.badge>
                            @endif
                        </div>

                        @if ($block->hint)
                            <p class="mt-1 text-xs leading-6 text-ink-500 dark:text-ink-400">{{ $block->hint }}</p>
                        @endif
                    </div>

                    <div class="flex items-center gap-1">
                        <button
                            type="button"
                            wire:click="moveBlock({{ $block->id }}, 'up')"
                            @disabled($block->is_locked || $index === 0)
                            class="flex items-center justify-center rounded-lg size-9 text-ink-500 hover:bg-ink-100 disabled:opacity-30 dark:hover:bg-ink-800"
                            aria-label="نقل لأعلى"
                        >
                            <x-icon name="chevron-up" :size="17" />
                        </button>

                        <button
                            type="button"
                            wire:click="moveBlock({{ $block->id }}, 'down')"
                            @disabled($block->is_locked || $index === $this->blocks->count() - 1)
                            class="flex items-center justify-center rounded-lg size-9 text-ink-500 hover:bg-ink-100 disabled:opacity-30 dark:hover:bg-ink-800"
                            aria-label="نقل لأسفل"
                        >
                            <x-icon name="chevron-down" :size="17" />
                        </button>

                        @unless ($block->is_locked)
                            <button
                                type="button"
                                wire:click="toggleBlock({{ $block->id }})"
                                class="flex items-center justify-center rounded-lg size-9 text-ink-500 hover:bg-ink-100 dark:hover:bg-ink-800"
                                aria-label="{{ $block->is_active ? 'إخفاء القسم' : 'إظهار القسم' }}"
                            >
                                <x-icon :name="$block->is_active ? 'eye' : 'close'" :size="17" />
                            </button>
                        @endunless

                        <x-ui.button type="button" x-on:click="open = ! open" variant="outline" size="sm" icon="pencil">
                            تحرير
                        </x-ui.button>
                    </div>
                </header>

                <div x-show="open" x-cloak class="px-5 pb-5 space-y-6 border-t border-ink-200 dark:border-ink-800">

                    {{-- نصوص القسم --}}
                    @if ($fields !== [])
                        <form wire:submit="saveBlockText({{ $block->id }})" class="grid gap-4 pt-5">
                            @foreach ($fields as $field => $label)
                                <x-ui.field :label="$label" :error="$errors->first('blockText.'.$block->id.'.'.$field)">
                                    @if ($field === 'body')
                                        <x-ui.textarea
                                            rows="3"
                                            wire:model="blockText.{{ $block->id }}.{{ $field }}"
                                            :invalid="$errors->has('blockText.'.$block->id.'.'.$field)"
                                        />
                                    @else
                                        <x-ui.input
                                            wire:model="blockText.{{ $block->id }}.{{ $field }}"
                                            :invalid="$errors->has('blockText.'.$block->id.'.'.$field)"
                                        />
                                    @endif
                                </x-ui.field>
                            @endforeach

                            <div>
                                <x-ui.button type="submit" variant="brand" size="sm" icon="check">حفظ النصّ</x-ui.button>
                            </div>
                        </form>
                    @endif

                    {{-- خانة صورة القسم — مكانها «صور الواجهة» لا هنا --}}
                    @if ($block->imageSlot())
                        <p class="flex flex-wrap items-center gap-2 p-4 text-xs rounded-xl bg-ink-50 text-ink-600 dark:bg-ink-950 dark:text-ink-400">
                            <x-icon name="image" :size="16" />
                            صورة هذا القسم تُبدَّل من
                            <a href="{{ route('admin.headers') }}" wire:navigate class="font-bold underline text-brand-600 dark:text-brand-400">صور الواجهة</a>
                        </p>
                    @endif

                    {{-- قسم الأسئلة يعرض ما في «الأسئلة الشائعة» --}}
                    @if ($block->key === 'faq')
                        <p class="flex flex-wrap items-center gap-2 p-4 text-xs rounded-xl bg-ink-50 text-ink-600 dark:bg-ink-950 dark:text-ink-400">
                            <x-icon name="help" :size="16" />
                            الأسئلة نفسها تُضاف وتُحرَّر من
                            <a href="{{ route('admin.faqs') }}" wire:navigate class="font-bold underline text-brand-600 dark:text-brand-400">الأسئلة الشائعة</a>
                        </p>
                    @endif

                    {{-- مجموعات المعرض --}}
                    @if ($source !== null)
                        <div class="pt-5 border-t border-ink-200 dark:border-ink-800">
                            <h3 class="text-xs font-extrabold text-ink-700 dark:text-ink-300">المجموعات المعروضة</h3>

                            <ul class="mt-3 space-y-2">
                                @forelse ($block->items as $itemIndex => $item)
                                    <li class="flex flex-wrap items-center gap-2 p-3 border rounded-xl border-ink-200 dark:border-ink-800">
                                        <span class="flex-1 min-w-0 text-sm font-bold text-ink-800 dark:text-ink-200">
                                            {{ $item->post?->title ?? 'مجموعة محذوفة' }}

                                            @if ($item->post && $item->post->status !== 'published')
                                                <x-ui.badge variant="warning">مسودّة — لا تظهر</x-ui.badge>
                                            @endif
                                        </span>

                                        @if ($item->post)
                                            <a
                                                href="{{ route('admin.posts.edit', $item->post) }}"
                                                wire:navigate
                                                class="text-xs font-bold text-brand-600 dark:text-brand-400"
                                            >
                                                تحرير الصور
                                            </a>
                                        @endif

                                        <button type="button" wire:click="moveItem({{ $item->id }}, 'up')" @disabled($itemIndex === 0)
                                            class="flex items-center justify-center rounded-lg size-8 text-ink-500 hover:bg-ink-100 disabled:opacity-30 dark:hover:bg-ink-800" aria-label="نقل لأعلى">
                                            <x-icon name="chevron-up" :size="15" />
                                        </button>

                                        <button type="button" wire:click="moveItem({{ $item->id }}, 'down')" @disabled($itemIndex === $block->items->count() - 1)
                                            class="flex items-center justify-center rounded-lg size-8 text-ink-500 hover:bg-ink-100 disabled:opacity-30 dark:hover:bg-ink-800" aria-label="نقل لأسفل">
                                            <x-icon name="chevron-down" :size="15" />
                                        </button>

                                        <button type="button" wire:click="deleteItem({{ $item->id }})"
                                            wire:confirm="إزالة هذه المجموعة من الصفحة؟ صورها وأعمالها تبقى كما هي."
                                            class="flex items-center justify-center text-red-600 rounded-lg size-8 hover:bg-red-50 dark:hover:bg-red-950" aria-label="إزالة">
                                            <x-icon name="trash" :size="15" />
                                        </button>
                                    </li>
                                @empty
                                    <li class="p-4 text-xs rounded-xl bg-ink-50 text-ink-500 dark:bg-ink-950 dark:text-ink-400">
                                        لا مجموعة في هذا القسم — أضف واحدة ليظهر.
                                    </li>
                                @endforelse
                            </ul>

                            @php($addable = $this->addableGroups($block))

                            @if ($addable->isNotEmpty())
                                <div class="flex flex-wrap items-end gap-2 mt-3">
                                    <x-ui.field label="أضف مجموعة" class="flex-1 min-w-56">
                                        <x-ui.select wire:model="newGroup.{{ $block->id }}">
                                            <option value="">— اختر —</option>
                                            @foreach ($addable as $post)
                                                <option value="{{ $post->id }}">
                                                    {{ $post->title }}{{ $post->status === 'published' ? '' : ' (مسودّة)' }}
                                                </option>
                                            @endforeach
                                        </x-ui.select>
                                    </x-ui.field>

                                    <x-ui.button type="button" wire:click="addGroup({{ $block->id }})" variant="outline" size="sm" icon="plus">
                                        إضافة
                                    </x-ui.button>
                                </div>
                            @endif
                        </div>
                    @endif

                    {{-- العناصر المتكرّرة --}}
                    @if ($itemFields !== [])
                        <div class="pt-5 border-t border-ink-200 dark:border-ink-800">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <h3 class="text-xs font-extrabold text-ink-700 dark:text-ink-300">
                                    {{ $this->itemLabelOf($block) }} ({{ $block->items->count() }})
                                </h3>

                                <x-ui.button type="button" wire:click="startItem({{ $block->id }})" variant="outline" size="sm" icon="plus">
                                    أضف {{ $this->itemLabelOf($block) }}
                                </x-ui.button>
                            </div>

                            <div class="mt-3 space-y-3">
                                @foreach ($block->items as $itemIndex => $item)
                                    <div @class([
                                        'p-4 border rounded-xl border-ink-200 dark:border-ink-800',
                                        'opacity-60' => ! $item->is_active,
                                    ])>
                                        <div class="flex flex-wrap items-center justify-between gap-2">
                                            <span class="flex items-center gap-2 text-sm font-bold text-ink-800 dark:text-ink-200">
                                                @if ($item->icon)
                                                    <x-icon :name="$item->icon" :size="16" />
                                                @endif
                                                {{ $item->title ?: 'بلا عنوان' }}
                                            </span>

                                            <div class="flex items-center gap-1">
                                                <button type="button" wire:click="moveItem({{ $item->id }}, 'up')" @disabled($itemIndex === 0)
                                                    class="flex items-center justify-center rounded-lg size-8 text-ink-500 hover:bg-ink-100 disabled:opacity-30 dark:hover:bg-ink-800" aria-label="نقل لأعلى">
                                                    <x-icon name="chevron-up" :size="15" />
                                                </button>

                                                <button type="button" wire:click="moveItem({{ $item->id }}, 'down')" @disabled($itemIndex === $block->items->count() - 1)
                                                    class="flex items-center justify-center rounded-lg size-8 text-ink-500 hover:bg-ink-100 disabled:opacity-30 dark:hover:bg-ink-800" aria-label="نقل لأسفل">
                                                    <x-icon name="chevron-down" :size="15" />
                                                </button>

                                                <button type="button" wire:click="toggleItem({{ $item->id }})"
                                                    class="flex items-center justify-center rounded-lg size-8 text-ink-500 hover:bg-ink-100 dark:hover:bg-ink-800"
                                                    aria-label="{{ $item->is_active ? 'إخفاء' : 'إظهار' }}">
                                                    <x-icon :name="$item->is_active ? 'eye' : 'close'" :size="15" />
                                                </button>

                                                <button type="button" wire:click="deleteItem({{ $item->id }})"
                                                    wire:confirm="حذف هذا العنصر نهائيًا؟"
                                                    class="flex items-center justify-center text-red-600 rounded-lg size-8 hover:bg-red-50 dark:hover:bg-red-950" aria-label="حذف">
                                                    <x-icon name="trash" :size="15" />
                                                </button>
                                            </div>
                                        </div>

                                        <form wire:submit="saveItem({{ $item->id }})" class="grid gap-3 mt-4">
                                            @foreach ($itemFields as $field => $label)
                                                <x-ui.field :label="$label" :error="$errors->first('itemText.'.$item->id.'.'.$field)">
                                                    @if ($field === 'icon')
                                                        <x-ui.select wire:model="itemText.{{ $item->id }}.icon">
                                                            <option value="">بلا أيقونة</option>
                                                            @foreach (App\Models\PageBlockItem::icons() as $key => $name)
                                                                <option value="{{ $key }}">{{ $name }}</option>
                                                            @endforeach
                                                        </x-ui.select>
                                                    @elseif ($field === 'body')
                                                        <x-ui.textarea rows="3" wire:model="itemText.{{ $item->id }}.{{ $field }}" />
                                                    @else
                                                        <x-ui.input wire:model="itemText.{{ $item->id }}.{{ $field }}" />
                                                    @endif
                                                </x-ui.field>
                                            @endforeach

                                            <div>
                                                <x-ui.button type="submit" variant="brand" size="sm" icon="check">حفظ</x-ui.button>
                                            </div>
                                        </form>

                                        {{-- النقاط داخل العنصر --}}
                                        @if ($childFields !== [])
                                            <div class="pt-4 mt-4 border-t border-ink-200 dark:border-ink-800">
                                                <div class="flex flex-wrap items-center justify-between gap-2">
                                                    <h4 class="text-xs font-bold text-ink-600 dark:text-ink-400">
                                                        {{ $this->childLabelOf($block) }} ({{ $item->children->count() }})
                                                    </h4>

                                                    <x-ui.button type="button" wire:click="startItem({{ $block->id }}, {{ $item->id }})" variant="outline" size="sm" icon="plus">
                                                        أضف
                                                    </x-ui.button>
                                                </div>

                                                <div class="mt-3 space-y-3">
                                                    @foreach ($item->children as $childIndex => $child)
                                                        <form wire:submit="saveItem({{ $child->id }})" class="p-3 rounded-lg bg-ink-50 dark:bg-ink-950">
                                                            <div class="grid gap-3">
                                                                @foreach ($childFields as $field => $label)
                                                                    <x-ui.field :label="$label" :error="$errors->first('itemText.'.$child->id.'.'.$field)">
                                                                        <x-ui.input wire:model="itemText.{{ $child->id }}.{{ $field }}" />
                                                                    </x-ui.field>
                                                                @endforeach
                                                            </div>

                                                            <div class="flex items-center gap-1 mt-3">
                                                                <x-ui.button type="submit" variant="brand" size="sm" icon="check">حفظ</x-ui.button>

                                                                <button type="button" wire:click="moveItem({{ $child->id }}, 'up')" @disabled($childIndex === 0)
                                                                    class="flex items-center justify-center rounded-lg size-8 text-ink-500 hover:bg-ink-100 disabled:opacity-30 dark:hover:bg-ink-800" aria-label="نقل لأعلى">
                                                                    <x-icon name="chevron-up" :size="15" />
                                                                </button>

                                                                <button type="button" wire:click="moveItem({{ $child->id }}, 'down')" @disabled($childIndex === $item->children->count() - 1)
                                                                    class="flex items-center justify-center rounded-lg size-8 text-ink-500 hover:bg-ink-100 disabled:opacity-30 dark:hover:bg-ink-800" aria-label="نقل لأسفل">
                                                                    <x-icon name="chevron-down" :size="15" />
                                                                </button>

                                                                <button type="button" wire:click="deleteItem({{ $child->id }})"
                                                                    wire:confirm="حذف هذه النقطة؟"
                                                                    class="flex items-center justify-center text-red-600 rounded-lg size-8 hover:bg-red-50 dark:hover:bg-red-950" aria-label="حذف">
                                                                    <x-icon name="trash" :size="15" />
                                                                </button>
                                                            </div>
                                                        </form>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </x-admin.card>
        @endforeach
    </div>

    {{-- نموذج العنصر الجديد — يظهر أسفل الصفحة فتُمرَّر إليه --}}
    @if ($addingToBlock !== null)
        @php($target = $this->blocks->firstWhere('id', $addingToBlock))

        <x-admin.reveal on="page-form-opened" class="mt-6">
            <x-admin.card
                :title="$addingToItem === null ? 'عنصر جديد في: '.($target?->label ?? '') : 'نقطة جديدة'"
                description="العنوان وحده مطلوب. ما تتركه فارغًا لا يظهر في الصفحة."
            >
                <form wire:submit="addItem" class="grid gap-4">
                    @php($newFields = $addingToItem === null
                        ? ($target ? $this->itemFieldsOf($target) : [])
                        : ($target ? $this->childFieldsOf($target) : []))

                    @foreach ($newFields as $field => $label)
                        <x-ui.field :label="$label" :error="$errors->first('newItem.'.$field)">
                            @if ($field === 'icon')
                                <x-ui.select wire:model="newItem.icon">
                                    <option value="">بلا أيقونة</option>
                                    @foreach (App\Models\PageBlockItem::icons() as $key => $name)
                                        <option value="{{ $key }}">{{ $name }}</option>
                                    @endforeach
                                </x-ui.select>
                            @elseif ($field === 'body')
                                <x-ui.textarea rows="3" wire:model="newItem.body" :invalid="$errors->has('newItem.body')" />
                            @else
                                <x-ui.input wire:model="newItem.{{ $field }}" :invalid="$errors->has('newItem.'.$field)" />
                            @endif
                        </x-ui.field>
                    @endforeach

                    <div class="flex gap-2">
                        <x-ui.button type="submit" variant="brand" icon="check">إضافة</x-ui.button>
                        <x-ui.button type="button" wire:click="cancelItem" variant="outline">إلغاء</x-ui.button>
                    </div>
                </form>
            </x-admin.card>
        </x-admin.reveal>
    @endif
</div>
