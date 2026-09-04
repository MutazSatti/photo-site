<?php

use App\Models\Category;
use App\Models\Post;
use App\Models\Section;
use App\Support\SectionRoutes;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts::admin', ['title' => 'الأقسام'])] class extends Component
{
    /** نوع ما يجري تحريره: section أو category */
    public ?string $editingType = null;

    public ?int $editingId = null;

    public ?int $parentSectionId = null;

    public string $name = '';

    public string $name_en = '';

    public string $slug = '';

    public string $tagline = '';

    public string $description = '';

    public string $icon = 'camera';

    /** لون القسم الرئيسي — الأقسام الفرعية ترثه فلا حقل لون لها. */
    public string $color = 'brand';

    public string $sort_order = '0';

    public bool $is_active = true;

    public string $seo_title = '';

    public string $seo_description = '';

    /** الأيقونات المتاحة — كلها رسوم SVG معرّفة في مكوّن x-icon */
    public array $iconOptions = [
        'camera' => 'كاميرا',
        'aperture' => 'عدسة',
        'academic' => 'تدريب',
        'document' => 'مستند',
        'lightbulb' => 'فكرة',
        'sparkles' => 'مناسبات',
        'presentation' => 'مؤتمرات',
        'building' => 'عقارات',
        'drone' => 'تصوير جوي',
        'images' => 'صور',
        'users' => 'أشخاص',
        'star' => 'نجمة',
    ];

    #[Computed]
    public function sections()
    {
        return Section::query()
            ->ordered()
            ->with(['categories' => fn ($q) => $q->withCount('posts')])
            ->withCount('posts')
            ->get();
    }

    /** القسم الرئيسي الذي ينتمي إليه القسم الفرعي قيد التحرير. */
    #[Computed]
    public function parentSection(): ?Section
    {
        return $this->parentSectionId
            ? $this->sections->firstWhere('id', $this->parentSectionId)
            : null;
    }

    public function newSection(): void
    {
        $this->resetForm();
        $this->editingType = 'section';
        $this->editingId = null;
        $this->sort_order = (string) (($this->sections->max('sort_order') ?? -1) + 1);
    }

    public function editSection(int $id): void
    {
        $section = Section::findOrFail($id);

        $this->resetForm();
        $this->editingType = 'section';
        $this->editingId = $section->id;
        // الإسناد صريح لأن الأعمدة قد تكون null بينما الخصائص مطبوعة كنصوص
        $this->name = $section->name;
        $this->slug = $section->slug;
        $this->icon = $section->icon;
        $this->color = $section->color;
        $this->name_en = (string) $section->name_en;
        $this->tagline = (string) $section->tagline;
        $this->description = (string) $section->description;
        $this->seo_title = (string) $section->seo_title;
        $this->seo_description = (string) $section->seo_description;
        $this->sort_order = (string) $section->sort_order;
        $this->is_active = $section->is_active;
    }

    public function newCategory(int $sectionId): void
    {
        $this->resetForm();
        $this->editingType = 'category';
        $this->editingId = null;
        $this->parentSectionId = $sectionId;

        $siblings = $this->sections->firstWhere('id', $sectionId)?->categories;
        $this->sort_order = (string) (($siblings?->max('sort_order') ?? -1) + 1);
    }

    public function editCategory(int $id): void
    {
        $category = Category::findOrFail($id);

        $this->resetForm();
        $this->editingType = 'category';
        $this->editingId = $category->id;
        $this->parentSectionId = $category->section_id;
        $this->name = $category->name;
        $this->name_en = (string) $category->name_en;
        $this->slug = $category->slug;
        $this->tagline = (string) $category->tagline;
        $this->description = (string) $category->description;
        $this->icon = $category->icon;
        $this->sort_order = (string) $category->sort_order;
        $this->is_active = $category->is_active;
        $this->seo_title = (string) $category->seo_title;
        $this->seo_description = (string) $category->seo_description;
    }

    /** الاسم الإنجليزي هو أقرب مصدر لرابط لاتيني، فالاسم العربي لا يصلح له. */
    public function updatedNameEn(string $value): void
    {
        if ($this->editingId === null && $this->slug === '') {
            $this->slug = str($value)->slug()->value();
        }
    }

    public function save(): void
    {
        $data = $this->validate([
            'name' => ['required', 'string', 'max:120'],
            'name_en' => ['nullable', 'string', 'max:120'],
            'slug' => ['required', 'string', 'max:120', 'regex:/^[a-z0-9-]+$/'],
            'tagline' => ['nullable', 'string', 'max:160'],
            'description' => ['nullable', 'string', 'max:2000'],
            'icon' => ['required', 'string', 'max:40'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999'],
            'seo_title' => ['nullable', 'string', 'max:180'],
            'seo_description' => ['nullable', 'string', 'max:320'],
        ], [
            'slug.regex' => 'الرابط يقبل الحروف اللاتينية الصغيرة والأرقام والشرطة فقط.',
        ]);

        $payload = [
            ...$data,
            'sort_order' => (int) ($this->sort_order ?: 0),
            'is_active' => $this->is_active,
        ];

        $saved = $this->editingType === 'section'
            ? $this->saveSection($payload)
            : $this->saveCategory($payload);

        if (! $saved) {
            return;
        }

        $this->resetForm();
        unset($this->sections);
        $this->flushCaches();

        $this->dispatch('notify', message: 'حُفظت التغييرات.');
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function saveSection(array $payload): bool
    {
        // اللون خاص بالقسم الرئيسي وحده — القسم الفرعي يرثه، فلا يُتحقَّق منه إلا هنا
        $this->validate([
            'color' => ['required', 'string', Rule::in(array_keys(config('site.section_colors')))],
        ], [], ['color' => 'اللون']);

        $payload['color'] = $this->color;

        // رابط القسم الرئيسي هو المقطع الأول في العنوان، فلا يصح أن يحجب صفحة ثابتة
        if (in_array($this->slug, SectionRoutes::RESERVED_SLUGS, true)) {
            $this->addError('slug', 'هذا الرابط محجوز لصفحة ثابتة في الموقع، اختر غيره.');

            return false;
        }

        $taken = Section::where('slug', $this->slug)
            ->when($this->editingId, fn ($q) => $q->whereKeyNot($this->editingId))
            ->exists();

        if ($taken) {
            $this->addError('slug', 'هذا الرابط مستخدم في قسم رئيسي آخر.');

            return false;
        }

        $this->editingId
            ? Section::findOrFail($this->editingId)->update($payload)
            : Section::create([...$payload, 'has_categories' => false]);

        return true;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function saveCategory(array $payload): bool
    {
        $taken = Category::where('section_id', $this->parentSectionId)
            ->where('slug', $this->slug)
            ->when($this->editingId, fn ($q) => $q->whereKeyNot($this->editingId))
            ->exists();

        if ($taken) {
            $this->addError('slug', 'هذا الرابط مستخدم في قسم فرعي آخر داخل القسم نفسه.');

            return false;
        }

        // عنصرٌ مباشر تحت القسم يحمل الرابط نفسه سيُحجب، لأن القسم الفرعي أسبق في المطابقة
        $shadowed = Post::where('section_id', $this->parentSectionId)
            ->whereNull('category_id')
            ->where('slug', $this->slug)
            ->exists();

        if ($shadowed) {
            $this->addError('slug', 'يوجد عنصر داخل القسم يحمل هذا الرابط، وسيحجبه القسم الفرعي.');

            return false;
        }

        $payload['section_id'] = $this->parentSectionId;

        $this->editingId
            ? Category::findOrFail($this->editingId)->update($payload)
            : Category::create($payload);

        $this->syncHasCategories($this->parentSectionId);

        return true;
    }

    /**
     * ينقل قسمًا خطوة أعلى أو أسفل بتبديل ترتيبه مع جاره.
     *
     * التبديل مع الجار المباشر لا إعادة ترقيم الكل: يبقى ترتيب بقية الأقسام
     * كما هو، والنتيجة متوقّعة حتى لو كانت الأرقام متباعدة أو متكرّرة.
     */
    public function moveSection(int $id, string $direction): void
    {
        $ordered = Section::query()->ordered()->get()->values();
        $from = $ordered->search(fn (Section $s) => $s->id === $id);

        if ($from === false) {
            return;
        }

        $to = $direction === 'up' ? $from - 1 : $from + 1;

        if ($to < 0 || $to >= $ordered->count()) {
            return;
        }

        // ينتقل العنصر إلى موضعه الجديد، ثم يُكتب الترتيب من الصفر تصاعديًا.
        // إعادة الترقيم الكاملة تصحّح أي أرقام متكرّرة أو متباعدة سبقت.
        $reordered = $ordered->all();
        [$reordered[$from], $reordered[$to]] = [$reordered[$to], $reordered[$from]];

        foreach ($reordered as $position => $section) {
            if ($section->sort_order !== $position) {
                $section->update(['sort_order' => $position]);
            }
        }

        unset($this->sections);
        $this->flushCaches();
    }

    public function deleteSection(int $id): void
    {
        $section = Section::withCount('posts')->findOrFail($id);

        if ($section->posts_count > 0) {
            $this->dispatch('notify', variant: 'danger', message: 'انقل عناصر هذا القسم أو احذفها أولًا ثم احذف القسم.');

            return;
        }

        if ($this->editingType === 'section' && $this->editingId === $section->id) {
            $this->resetForm();
        }

        // الأقسام الفرعية الفارغة تُحذف تتاليًا مع القسم
        $section->delete();

        unset($this->sections);
        $this->flushCaches();

        $this->dispatch('notify', message: 'حُذف القسم الرئيسي.');
    }

    public function deleteCategory(int $id): void
    {
        $category = Category::withCount('posts')->findOrFail($id);

        if ($category->posts_count > 0) {
            $this->dispatch('notify', variant: 'danger', message: 'انقل عناصر هذا القسم الفرعي أولًا ثم احذفه.');

            return;
        }

        if ($this->editingType === 'category' && $this->editingId === $category->id) {
            $this->resetForm();
        }

        $sectionId = $category->section_id;

        $category->delete();

        $this->syncHasCategories($sectionId);

        unset($this->sections);
        $this->flushCaches();

        $this->dispatch('notify', message: 'حُذف القسم الفرعي.');
    }

    public function toggleActive(string $type, int $id): void
    {
        $model = $type === 'section' ? Section::findOrFail($id) : Category::findOrFail($id);

        $model->update(['is_active' => ! $model->is_active]);

        unset($this->sections);
        $this->flushCaches();
    }

    public function resetForm(): void
    {
        $this->reset([
            'editingType', 'editingId', 'parentSectionId', 'name', 'name_en', 'slug',
            'tagline', 'description', 'sort_order', 'seo_title', 'seo_description',
        ]);

        $this->icon = 'camera';
        $this->color = 'brand';
        $this->is_active = true;
        $this->resetErrorBag();
        unset($this->parentSection);
    }

    /** العلم يبقى مطابقًا للواقع: القسم يملك أقسامًا فرعية أو لا يملك. */
    private function syncHasCategories(?int $sectionId): void
    {
        $section = $sectionId ? Section::find($sectionId) : null;

        $section?->update(['has_categories' => $section->categories()->exists()]);
    }

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
        title="الأقسام"
        description="أقسام المعرض وأقسامها الفرعية. أضِف وعدّل واحذف كما تشاء، وكل قسم رئيسي يقبل أقسامًا فرعية. النصوص هنا تظهر في الصفحات وفي البيانات المهيكلة."
    >
        <x-slot:actions>
            <x-ui.button wire:click="newSection" icon="plus">قسم رئيسي جديد</x-ui.button>
        </x-slot:actions>
    </x-admin.page-header>

    <div class="grid gap-6 lg:grid-cols-3">

        {{-- ================= القائمة ================= --}}
        <div class="space-y-4 lg:col-span-2">
            @forelse ($this->sections as $section)
                <x-admin.card :padded="false">
                    <div class="flex flex-wrap items-center gap-3 px-5 py-4 sec-theme" style="{{ $section->colorStyle() }}">
                        {{-- ترتيب الأقسام يحدّد ترتيب ظهورها في القوائم وفي الصفحة الرئيسية --}}
                        <div class="flex flex-col -my-1">
                            <button type="button" wire:click="moveSection({{ $section->id }}, 'up')"
                                @disabled($loop->first)
                                class="p-0.5 text-ink-400 transition-colors hover:text-ink-700 disabled:opacity-25 dark:hover:text-ink-200"
                                aria-label="نقل {{ $section->name }} لأعلى">
                                <x-icon name="chevron-up" :size="15" />
                            </button>

                            <button type="button" wire:click="moveSection({{ $section->id }}, 'down')"
                                @disabled($loop->last)
                                class="p-0.5 text-ink-400 transition-colors hover:text-ink-700 disabled:opacity-25 dark:hover:text-ink-200"
                                aria-label="نقل {{ $section->name }} لأسفل">
                                <x-icon name="chevron-down" :size="15" />
                            </button>
                        </div>

                        <span class="flex items-center justify-center rounded-xl size-10 sec-bg-soft sec-text">
                            <x-icon :name="$section->icon" :size="19" />
                        </span>

                        <div class="min-w-0">
                            <div class="flex items-center gap-2">
                                <h2 class="text-sm font-extrabold text-ink-900 dark:text-ink-100">{{ $section->name }}</h2>
                                <span class="text-xs text-ink-400" dir="ltr">/{{ $section->slug }}</span>
                            </div>
                            <p class="mt-0.5 text-xs text-ink-500 dark:text-ink-400">
                                {{ $section->posts_count }} عنصر · {{ $section->categories->count() }} قسم فرعي
                            </p>
                        </div>

                        <div class="flex items-center gap-1 ms-auto">
                            <button type="button" wire:click="toggleActive('section', {{ $section->id }})"
                                class="rounded-lg px-2.5 py-1 text-xs font-bold transition-colors {{ $section->is_active
                                    ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-400'
                                    : 'bg-ink-100 text-ink-500 dark:bg-ink-800' }}">
                                {{ $section->is_active ? 'ظاهر' : 'مخفي' }}
                            </button>

                            <button type="button" wire:click="editSection({{ $section->id }})"
                                class="p-2 transition-colors rounded-lg text-ink-500 hover:bg-ink-100 dark:hover:bg-ink-800"
                                aria-label="تعديل القسم">
                                <x-icon name="pencil" :size="16" />
                            </button>

                            <button type="button" wire:click="deleteSection({{ $section->id }})"
                                wire:confirm="حذف القسم الرئيسي وأقسامه الفرعية الفارغة؟"
                                class="p-2 text-red-600 transition-colors rounded-lg hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-950/40"
                                aria-label="حذف القسم">
                                <x-icon name="trash" :size="16" />
                            </button>
                        </div>
                    </div>

                    <div class="px-5 py-4 border-t bg-ink-50 border-ink-200 dark:border-ink-800 dark:bg-ink-950/40">
                        <div class="flex items-center justify-between gap-3 mb-3">
                            <h3 class="text-xs font-extrabold text-ink-600 dark:text-ink-400">الأقسام الفرعية</h3>
                            <x-ui.button wire:click="newCategory({{ $section->id }})" variant="ghost" size="sm" icon="plus">
                                إضافة
                            </x-ui.button>
                        </div>

                        <ul class="space-y-2">
                            @forelse ($section->categories as $category)
                                <li class="flex flex-wrap items-center gap-3 px-3 py-2 bg-white border rounded-xl border-ink-200 dark:border-ink-800 dark:bg-ink-900">
                                    <span class="text-ink-400"><x-icon :name="$category->icon" :size="15" /></span>

                                    <div class="min-w-0">
                                        <p class="text-sm font-bold text-ink-800 dark:text-ink-200">{{ $category->name }}</p>
                                        <p class="text-xs text-ink-400" dir="ltr">/{{ $section->slug }}/{{ $category->slug }} · {{ $category->posts_count }}</p>
                                    </div>

                                    <div class="flex items-center gap-1 ms-auto">
                                        <button type="button" wire:click="toggleActive('category', {{ $category->id }})"
                                            class="rounded-lg px-2 py-0.5 text-xs font-bold {{ $category->is_active
                                                ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-400'
                                                : 'bg-ink-100 text-ink-500 dark:bg-ink-800' }}">
                                            {{ $category->is_active ? 'ظاهر' : 'مخفي' }}
                                        </button>

                                        <button type="button" wire:click="editCategory({{ $category->id }})"
                                            class="p-1.5 rounded-lg text-ink-500 transition-colors hover:bg-ink-100 dark:hover:bg-ink-800"
                                            aria-label="تعديل">
                                            <x-icon name="pencil" :size="14" />
                                        </button>

                                        <button type="button" wire:click="deleteCategory({{ $category->id }})"
                                            wire:confirm="حذف هذا القسم الفرعي؟"
                                            class="p-1.5 rounded-lg text-red-600 transition-colors hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-950/40"
                                            aria-label="حذف">
                                            <x-icon name="trash" :size="14" />
                                        </button>
                                    </div>
                                </li>
                            @empty
                                <li class="py-3 text-xs text-center text-ink-400">لا توجد أقسام فرعية بعد.</li>
                            @endforelse
                        </ul>
                    </div>
                </x-admin.card>
            @empty
                <x-admin.card>
                    <p class="py-8 text-sm text-center text-ink-500 dark:text-ink-400">
                        لا توجد أقسام بعد. ابدأ بإضافة قسم رئيسي.
                    </p>
                </x-admin.card>
            @endforelse
        </div>

        {{-- ================= نموذج التحرير ================= --}}
        <div>
            <div class="lg:sticky lg:top-20">
                @if ($editingType)
                    @php
                        $formTitle = $editingType === 'section'
                            ? ($editingId ? 'تعديل قسم رئيسي' : 'قسم رئيسي جديد')
                            : ($editingId ? 'تعديل قسم فرعي' : 'قسم فرعي جديد');

                        $slugHint = $editingType === 'section'
                            ? 'المقطع الأول في عنوان الصفحة. تغييره يغيّر روابط القسم وكل ما بداخله.'
                            : 'يظهر بعد رابط القسم الرئيسي.';
                    @endphp

                    <x-admin.card
                        :title="$formTitle"
                        :description="$editingType === 'category' ? 'داخل: ' . ($this->parentSection?->name ?? '—') : null"
                    >
                        <form wire:submit="save" class="grid gap-5">
                            <x-ui.field label="الاسم" required :error="$errors->first('name')">
                                <x-ui.input wire:model="name" :invalid="$errors->has('name')" />
                            </x-ui.field>

                            <x-ui.field label="الاسم بالإنجليزية" :error="$errors->first('name_en')"
                                hint="يُنشر في البيانات المهيكلة كاسم بديل، ويقترح الرابط عند الإضافة.">
                                <x-ui.input wire:model.blur="name_en" dir="ltr" />
                            </x-ui.field>

                            <x-ui.field label="الرابط" required :error="$errors->first('slug')" :hint="$slugHint">
                                <x-ui.input wire:model="slug" dir="ltr" :invalid="$errors->has('slug')" />
                            </x-ui.field>

                            <x-ui.field label="الجملة التعريفية" :error="$errors->first('tagline')">
                                <x-ui.input wire:model="tagline" />
                            </x-ui.field>

                            <x-ui.field label="الوصف" :error="$errors->first('description')"
                                hint="يظهر في رأس الصفحة وفي وصف السيو الافتراضي.">
                                <x-ui.textarea wire:model="description" rows="5" />
                            </x-ui.field>

                            <x-ui.field label="الأيقونة" :error="$errors->first('icon')">
                                <div class="grid grid-cols-6 gap-2">
                                    @foreach ($iconOptions as $key => $label)
                                        <button
                                            type="button"
                                            wire:click="$set('icon', '{{ $key }}')"
                                            title="{{ $label }}"
                                            class="flex items-center justify-center transition-colors border aspect-square rounded-xl {{ $icon === $key
                                                ? 'border-brand-500 bg-brand-50 text-brand-600 dark:bg-brand-950 dark:text-brand-400'
                                                : 'border-ink-200 text-ink-500 hover:border-ink-300 dark:border-ink-700' }}"
                                        >
                                            <x-icon :name="$key" :size="17" />
                                        </button>
                                    @endforeach
                                </div>
                            </x-ui.field>

                            @if ($editingType === 'section')
                                <x-ui.field
                                    label="لون القسم"
                                    :error="$errors->first('color')"
                                    hint="يميّز القسم في بطاقاته وصفحته وشارات أعماله. الأقسام الفرعية ترث لون قسمها."
                                >
                                    <div class="flex flex-wrap gap-2">
                                        @foreach (config('site.section_colors') as $key => $swatch)
                                            <button
                                                type="button"
                                                wire:click="$set('color', '{{ $key }}')"
                                                title="{{ $swatch['label'] }}"
                                                aria-label="{{ $swatch['label'] }}"
                                                aria-pressed="{{ $color === $key ? 'true' : 'false' }}"
                                                class="relative flex items-center justify-center transition-transform rounded-full size-9 ring-offset-2 ring-offset-white dark:ring-offset-ink-900 {{ $color === $key ? 'ring-2 ring-ink-900 dark:ring-ink-100' : 'hover:scale-110' }}"
                                                style="background-color: {{ $swatch['light'] }}"
                                            >
                                                @if ($color === $key)
                                                    <span class="text-white"><x-icon name="check" :size="15" /></span>
                                                @endif
                                            </button>
                                        @endforeach
                                    </div>
                                </x-ui.field>
                            @endif

                            <div class="grid grid-cols-2 gap-4">
                                <x-ui.field label="الترتيب" :error="$errors->first('sort_order')">
                                    <x-ui.input wire:model="sort_order" type="number" min="0" dir="ltr" />
                                </x-ui.field>

                                <x-ui.field label="الظهور">
                                    <label class="inline-flex items-center gap-2 py-2.5 text-sm cursor-pointer text-ink-700 dark:text-ink-300">
                                        <input type="checkbox" wire:model="is_active"
                                            class="border rounded size-4 border-ink-300 text-brand-500 dark:border-ink-600 dark:bg-ink-800">
                                        ظاهر
                                    </label>
                                </x-ui.field>
                            </div>

                            <x-ui.field label="عنوان السيو" :error="$errors->first('seo_title')">
                                <x-ui.input wire:model="seo_title" />
                            </x-ui.field>

                            <x-ui.field label="وصف السيو" :error="$errors->first('seo_description')">
                                <x-ui.textarea wire:model="seo_description" rows="3" />
                            </x-ui.field>

                            <div class="flex gap-2">
                                <x-ui.button type="submit" icon="check">حفظ</x-ui.button>
                                <x-ui.button wire:click="resetForm" variant="ghost">إلغاء</x-ui.button>
                            </div>
                        </form>
                    </x-admin.card>
                @else
                    <x-admin.card>
                        <p class="py-8 text-sm text-center text-ink-500 dark:text-ink-400">
                            اختر قسمًا لتحريره، أو أضف قسمًا رئيسيًا أو فرعيًا جديدًا.
                        </p>
                    </x-admin.card>
                @endif
            </div>
        </div>
    </div>
</div>
