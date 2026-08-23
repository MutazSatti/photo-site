<?php

use App\Models\Category;
use App\Models\Media;
use App\Models\Post;
use App\Models\Section;
use App\Services\ImageService;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Layout('layouts::admin', ['title' => 'تحرير عنصر'])] class extends Component
{
    use WithFileUploads;

    public ?Post $post = null;

    // ---- الحقول الأساسية ----
    public string $title = '';

    public string $slug = '';

    public string $subtitle = '';

    public string $excerpt = '';

    public string $body = '';

    public ?int $section_id = null;

    public ?int $category_id = null;

    // ---- تفاصيل اختيارية ----
    public string $location = '';

    public string $client = '';

    public string $event_date = '';

    public string $price = '';

    public string $duration = '';

    public string $seats = '';

    // ---- النشر ----
    public string $status = 'published';

    public bool $is_featured = false;

    public string $sort_order = '0';

    // ---- السيو ----
    public string $seo_title = '';

    public string $seo_description = '';

    public string $keywords = '';

    // ---- الصور ----
    /** @var array<int, \Livewire\Features\SupportFileUploads\TemporaryUploadedFile> */
    public array $uploads = [];

    public ?int $editingMediaId = null;

    public string $mediaAlt = '';

    public string $mediaCaption = '';

    public function mount(?Post $post = null): void
    {
        $this->post = $post?->exists ? $post : null;

        if ($this->post) {
            $this->fillFromPost($this->post);

            return;
        }

        $this->section_id = Section::query()->active()->ordered()->value('id');
        $this->status = 'draft';
    }

    private function fillFromPost(Post $post): void
    {
        $this->title = $post->title;
        $this->slug = $post->slug;
        $this->subtitle = (string) $post->subtitle;
        $this->excerpt = (string) $post->excerpt;
        $this->body = (string) $post->body;
        $this->section_id = $post->section_id;
        $this->category_id = $post->category_id;
        $this->location = (string) $post->location;
        $this->client = (string) $post->client;
        $this->event_date = $post->event_date?->toDateString() ?? '';
        $this->price = $post->price !== null ? (string) $post->price : '';
        $this->duration = (string) $post->duration;
        $this->seats = $post->seats !== null ? (string) $post->seats : '';
        $this->status = $post->status;
        $this->is_featured = $post->is_featured;
        $this->sort_order = (string) $post->sort_order;
        $this->seo_title = (string) $post->seo_title;
        $this->seo_description = (string) $post->seo_description;
        $this->keywords = implode('، ', $post->keywords ?? []);
    }

    /** يولّد رابطًا لاتينيًا من العنوان العربي عبر النقحرة. */
    public function updatedTitle(string $value): void
    {
        if ($this->post) {
            return;
        }

        $this->slug = str($value)->ascii()->slug()->value()
            ?: 'item-'.str()->lower(str()->random(6));
    }

    /** تبديل القسم يُسقط القسم الفرعي لأنه لم يعد ينتمي إليه. */
    public function updatedSectionId(): void
    {
        $this->category_id = null;
    }

    #[Computed]
    public function sections()
    {
        return Section::query()->active()->ordered()->get();
    }

    #[Computed]
    public function categories()
    {
        if (! $this->section_id) {
            return collect();
        }

        return Category::query()->active()->ordered()->where('section_id', $this->section_id)->get();
    }

    #[Computed]
    public function requiresCategory(): bool
    {
        return $this->categories->isNotEmpty();
    }

    #[Computed]
    public function media()
    {
        return $this->post
            ? Media::where('post_id', $this->post->id)->orderBy('sort_order')->orderBy('id')->get()
            : collect();
    }

    #[Computed]
    public function webpReady(): bool
    {
        return ImageService::webpSupported();
    }

    protected function rules(): array
    {
        return [
            'title' => ['required', 'string', 'min:3', 'max:180'],
            'slug' => [
                'required', 'string', 'max:180', 'regex:/^[a-z0-9-]+$/',
                Rule::unique('posts', 'slug')->ignore($this->post?->id),
            ],
            'subtitle' => ['nullable', 'string', 'max:180'],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'body' => ['nullable', 'string'],
            'section_id' => ['required', 'exists:sections,id'],
            'category_id' => [$this->requiresCategory ? 'required' : 'nullable', 'nullable', 'exists:categories,id'],
            'location' => ['nullable', 'string', 'max:120'],
            'client' => ['nullable', 'string', 'max:120'],
            'event_date' => ['nullable', 'date'],
            'price' => ['nullable', 'numeric', 'min:0', 'max:9999999'],
            'duration' => ['nullable', 'string', 'max:80'],
            'seats' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'status' => ['required', 'in:draft,published'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'seo_title' => ['nullable', 'string', 'max:180'],
            'seo_description' => ['nullable', 'string', 'max:320'],
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'title' => 'العنوان',
            'slug' => 'الرابط',
            'excerpt' => 'الملخّص',
            'section_id' => 'القسم',
            'category_id' => 'القسم الفرعي',
            'event_date' => 'التاريخ',
            'price' => 'السعر',
            'seats' => 'المقاعد',
        ];
    }

    protected function messages(): array
    {
        return [
            'slug.regex' => 'الرابط يقبل الحروف اللاتينية الصغيرة والأرقام والشرطة فقط.',
            'slug.unique' => 'هذا الرابط مستخدم في عنصر آخر.',
        ];
    }

    public function save(bool $andPublish = false): void
    {
        if ($andPublish) {
            $this->status = 'published';
        }

        $data = $this->validate();

        $payload = [
            ...$data,
            'category_id' => $this->category_id ?: null,
            'event_date' => $this->event_date ?: null,
            'price' => $this->price !== '' ? $this->price : null,
            'seats' => $this->seats !== '' ? (int) $this->seats : null,
            'sort_order' => (int) ($this->sort_order ?: 0),
            'is_featured' => $this->is_featured,
            'keywords' => $this->keywordsArray(),
            'published_at' => $this->post?->published_at
                ?? ($this->status === 'published' ? now() : null),
        ];

        if ($this->post) {
            $this->post->update($payload);
        } else {
            $this->post = Post::create($payload);
        }

        $this->flushCaches();

        $this->dispatch('notify', message: 'حُفظ العنصر.');

        // الانتقال إلى صفحة التحرير يتيح رفع الصور مباشرة بعد الإنشاء
        if (! request()->routeIs('admin.posts.edit')) {
            $this->redirect(route('admin.posts.edit', $this->post), navigate: true);
        }
    }

    private function keywordsArray(): ?array
    {
        $items = collect(preg_split('/[،,]/u', $this->keywords))
            ->map(fn ($k) => trim($k))
            ->filter()
            ->values()
            ->all();

        return $items ?: null;
    }

    /**
     * كل صورة تمرّ من ImageService فتُحوَّل إلى WebP بعدة مقاسات.
     */
    public function saveUploads(ImageService $images): void
    {
        if (! $this->post) {
            $this->dispatch('notify', variant: 'danger', message: 'احفظ العنصر أولًا ثم أضف الصور.');

            return;
        }

        $this->validate([
            'uploads' => ['required', 'array', 'max:30'],
            'uploads.*' => ['image', 'mimes:jpg,jpeg,png,webp,gif,bmp', 'max:'.config('site.images.max_upload_kb')],
        ], [
            'uploads.*.image' => 'الملف يجب أن يكون صورة.',
            'uploads.*.max' => 'حجم الصورة يتجاوز الحد المسموح.',
        ]);

        $isFirstBatch = $this->media->isEmpty();

        foreach ($this->uploads as $index => $file) {
            $images->store(
                file: $file,
                post: $this->post,
                isCover: $isFirstBatch && $index === 0,
            );
        }

        $count = count($this->uploads);

        $this->reset('uploads');
        unset($this->media);

        $this->flushCaches();

        $this->dispatch('notify', message: "حُوّلت {$count} صورة إلى WebP وأُضيفت.");
    }

    public function makeCover(int $mediaId, ImageService $images): void
    {
        $images->makeCover(Media::findOrFail($mediaId));

        unset($this->media);
        $this->flushCaches();

        $this->dispatch('notify', message: 'حُدّدت صورة الغلاف.');
    }

    public function deleteMedia(int $mediaId): void
    {
        Media::findOrFail($mediaId)->delete();

        unset($this->media);
        $this->flushCaches();

        $this->dispatch('notify', message: 'حُذفت الصورة.');
    }

    public function editMedia(int $mediaId): void
    {
        $media = Media::findOrFail($mediaId);

        $this->editingMediaId = $media->id;
        $this->mediaAlt = (string) $media->alt;
        $this->mediaCaption = (string) $media->caption;
    }

    public function saveMediaMeta(): void
    {
        $this->validate([
            'mediaAlt' => ['required', 'string', 'max:180'],
            'mediaCaption' => ['nullable', 'string', 'max:180'],
        ], [
            'mediaAlt.required' => 'النص البديل مطلوب — عليه يعتمد ظهور الصورة في بحث الصور.',
        ]);

        Media::whereKey($this->editingMediaId)->update([
            'alt' => $this->mediaAlt,
            'caption' => $this->mediaCaption ?: null,
        ]);

        $this->reset(['editingMediaId', 'mediaAlt', 'mediaCaption']);
        unset($this->media);
        $this->flushCaches();

        $this->dispatch('notify', message: 'حُدّثت بيانات الصورة.');
    }

    public function moveMedia(int $mediaId, string $direction): void
    {
        $ordered = $this->media->pluck('id')->all();
        $index = array_search($mediaId, $ordered, true);

        if ($index === false) {
            return;
        }

        $target = $direction === 'up' ? $index - 1 : $index + 1;

        if ($target < 0 || $target >= count($ordered)) {
            return;
        }

        [$ordered[$index], $ordered[$target]] = [$ordered[$target], $ordered[$index]];

        app(ImageService::class)->reorder($ordered);

        unset($this->media);
        $this->flushCaches();
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
        :title="$post ? 'تعديل: ' . $post->title : 'إضافة عنصر جديد'"
        :description="$post ? 'الرابط: ' . $post->url() : 'اختر القسم، اكتب المحتوى، ثم احفظ لتتمكّن من رفع الصور.'"
    >
        <x-slot:actions>
            @if ($post)
                <x-ui.button href="{{ $post->url() }}" variant="outline" icon="eye" :navigate="false" target="_blank" rel="noopener">
                    معاينة
                </x-ui.button>
            @endif
            <x-ui.button href="{{ route('admin.posts') }}" variant="ghost" icon="arrow-right">رجوع</x-ui.button>
        </x-slot:actions>
    </x-admin.page-header>

    <form wire:submit="save" class="grid gap-6 lg:grid-cols-3">

        {{-- ================= العمود الأساسي ================= --}}
        <div class="space-y-6 lg:col-span-2">

            <x-admin.card title="المحتوى">
                <div class="grid gap-5">
                    <x-ui.field label="العنوان" for="title" required :error="$errors->first('title')">
                        <x-ui.input id="title" wire:model.live.debounce.500ms="title"
                            placeholder="مثال: تغطية حفل تخرّج — دفعة كاملة" :invalid="$errors->has('title')" />
                    </x-ui.field>

                    <x-ui.field label="الرابط (بالإنجليزية)" for="slug" required
                        hint="يظهر في عنوان الصفحة — يُولَّد تلقائيًا من العنوان ويمكن تعديله."
                        :error="$errors->first('slug')">
                        <x-ui.input id="slug" wire:model="slug" dir="ltr" placeholder="taghtiyat-hafl-takharruj"
                            :invalid="$errors->has('slug')" />
                    </x-ui.field>

                    <x-ui.field label="العنوان الفرعي" for="subtitle" :error="$errors->first('subtitle')">
                        <x-ui.input id="subtitle" wire:model="subtitle" placeholder="سطر قصير يوضّح الفكرة" />
                    </x-ui.field>

                    <x-ui.field label="الملخّص" for="excerpt"
                        hint="يظهر في بطاقة العنصر وفي نتائج البحث — اجعله واضحًا ومباشرًا."
                        :error="$errors->first('excerpt')">
                        <x-ui.textarea id="excerpt" wire:model="excerpt" rows="3"
                            placeholder="جملتان تصفان العمل ونتيجته." :invalid="$errors->has('excerpt')" />
                    </x-ui.field>

                    <x-ui.field label="المحتوى الكامل" for="body"
                        hint="يقبل وسوم HTML البسيطة: <p> <h2> <h3> <ul> <li> <strong> <a>"
                        :error="$errors->first('body')">
                        <x-ui.textarea id="body" wire:model="body" rows="14" dir="rtl"
                            class="font-mono text-xs leading-6"
                            placeholder="<p>نص الفقرة الأولى…</p>" />
                    </x-ui.field>
                </div>
            </x-admin.card>

            {{-- ================= الصور ================= --}}
            <x-admin.card
                title="الصور"
                description="تُحوَّل كل صورة تلقائيًا إلى WebP بثلاثة مقاسات لتحميل أسرع."
            >
                @unless ($this->webpReady)
                    <x-ui.alert variant="danger" title="التحويل غير متاح" class="mb-5">
                        إضافة GD غير مفعّلة في PHP، ورفع الصور سيفشل. فعّلها في php.ini ثم أعد تشغيل الخادم.
                    </x-ui.alert>
                @endunless

                @if (! $post)
                    <x-ui.alert variant="info">
                        احفظ العنصر أولًا، ثم يظهر هنا رفع الصور.
                    </x-ui.alert>
                @else
                    <div
                        x-data="{ dragging: false }"
                        x-on:dragover.prevent="dragging = true"
                        x-on:dragleave.prevent="dragging = false"
                        x-on:drop.prevent="dragging = false"
                        class="relative"
                    >
                        <label
                            class="flex flex-col items-center justify-center px-6 py-10 transition-colors border border-dashed cursor-pointer rounded-2xl"
                            x-bind:class="dragging
                                ? 'border-brand-500 bg-brand-50 dark:bg-brand-950/40'
                                : 'border-ink-300 hover:border-brand-400 dark:border-ink-700'"
                        >
                            <input type="file" wire:model="uploads" multiple accept="image/*" class="sr-only">

                            <span class="flex items-center justify-center mb-3 size-12 rounded-2xl bg-ink-100 text-ink-500 dark:bg-ink-800 dark:text-ink-400">
                                <x-icon name="upload" :size="22" />
                            </span>

                            <span class="text-sm font-extrabold text-ink-800 dark:text-ink-200">
                                اسحب الصور هنا أو اضغط للاختيار
                            </span>
                            <span class="mt-1 text-xs text-ink-500 dark:text-ink-400">
                                JPG أو PNG أو WebP — حتى {{ (int) (config('site.images.max_upload_kb') / 1024) }} ميجابايت للصورة
                            </span>
                        </label>

                        <div wire:loading wire:target="uploads" class="mt-3">
                            <p class="flex items-center gap-2 text-sm text-ink-500 dark:text-ink-400">
                                <span class="animate-spin"><x-icon name="refresh" :size="15" /></span>
                                جارٍ رفع الصور…
                            </p>
                        </div>

                        @error('uploads.*')
                            <p class="mt-3 text-sm font-bold text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror

                        @if ($uploads)
                            <div class="flex flex-wrap items-center gap-3 p-4 mt-4 bg-ink-50 rounded-xl dark:bg-ink-800">
                                <p class="text-sm font-bold text-ink-700 dark:text-ink-300">
                                    {{ count($uploads) }} صورة جاهزة للتحويل
                                </p>
                                <div class="flex gap-2 ms-auto">
                                    <x-ui.button wire:click="saveUploads" wire:loading.attr="disabled" variant="primary" size="sm" icon="check">
                                        <span wire:loading.remove wire:target="saveUploads">حوّل وأضف</span>
                                        <span wire:loading wire:target="saveUploads">جارٍ التحويل…</span>
                                    </x-ui.button>
                                    <x-ui.button wire:click="$set('uploads', [])" variant="ghost" size="sm">إلغاء</x-ui.button>
                                </div>
                            </div>
                        @endif
                    </div>

                    {{-- الصور المضافة --}}
                    @if ($this->media->isNotEmpty())
                        <ul class="grid gap-4 mt-6 sm:grid-cols-2">
                            @foreach ($this->media as $item)
                                <li class="overflow-hidden border rounded-2xl border-ink-200 dark:border-ink-800">
                                    <div class="relative bg-ink-100 aspect-4/3 dark:bg-ink-800">
                                        <x-site.picture :media="$item" variant="md" class="size-full" />

                                        @if ($item->is_cover)
                                            <span class="absolute rounded-full bg-brand-500 px-2.5 py-1 text-xs font-extrabold text-ink-950 top-2 start-2">
                                                الغلاف
                                            </span>
                                        @endif
                                    </div>

                                    <div class="p-3">
                                        <p class="text-xs truncate text-ink-600 dark:text-ink-400" title="{{ $item->altText() }}">
                                            {{ $item->altText() }}
                                        </p>
                                        <p class="mt-1 text-[11px] text-ink-400" dir="ltr">
                                            {{ $item->width }}×{{ $item->height }} · {{ number_format(($item->size ?? 0) / 1024) }} KB · WebP
                                        </p>

                                        <div class="flex flex-wrap items-center gap-1 mt-3">
                                            @unless ($item->is_cover)
                                                <button type="button" wire:click="makeCover({{ $item->id }})"
                                                    class="rounded-lg px-2 py-1 text-xs font-bold text-ink-600 transition-colors hover:bg-ink-100 dark:text-ink-400 dark:hover:bg-ink-800">
                                                    اجعلها الغلاف
                                                </button>
                                            @endunless

                                            <button type="button" wire:click="editMedia({{ $item->id }})"
                                                class="p-1.5 rounded-lg text-ink-500 transition-colors hover:bg-ink-100 dark:hover:bg-ink-800"
                                                aria-label="تعديل بيانات الصورة">
                                                <x-icon name="pencil" :size="14" />
                                            </button>

                                            <button type="button" wire:click="moveMedia({{ $item->id }}, 'up')"
                                                class="p-1.5 rounded-lg text-ink-500 transition-colors hover:bg-ink-100 dark:hover:bg-ink-800"
                                                aria-label="تقديم">
                                                <x-icon name="chevron-right" :size="14" />
                                            </button>

                                            <button type="button" wire:click="moveMedia({{ $item->id }}, 'down')"
                                                class="p-1.5 rounded-lg text-ink-500 transition-colors hover:bg-ink-100 dark:hover:bg-ink-800"
                                                aria-label="تأخير">
                                                <x-icon name="chevron-left" :size="14" />
                                            </button>

                                            <button type="button" wire:click="deleteMedia({{ $item->id }})"
                                                wire:confirm="حذف هذه الصورة نهائيًا؟"
                                                class="p-1.5 rounded-lg text-red-600 transition-colors ms-auto hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-950/40"
                                                aria-label="حذف الصورة">
                                                <x-icon name="trash" :size="14" />
                                            </button>
                                        </div>

                                        @if ($editingMediaId === $item->id)
                                            <div class="grid gap-3 pt-3 mt-3 border-t border-ink-200 dark:border-ink-800">
                                                <x-ui.field label="النص البديل" :error="$errors->first('mediaAlt')"
                                                    hint="وصف دقيق لما في الصورة — يقرؤه محرّك البحث وقارئ الشاشة.">
                                                    <x-ui.input wire:model="mediaAlt" :invalid="$errors->has('mediaAlt')" />
                                                </x-ui.field>

                                                <x-ui.field label="التعليق" :error="$errors->first('mediaCaption')">
                                                    <x-ui.input wire:model="mediaCaption" />
                                                </x-ui.field>

                                                <div class="flex gap-2">
                                                    <x-ui.button wire:click="saveMediaMeta" size="sm" icon="check">حفظ</x-ui.button>
                                                    <x-ui.button wire:click="$set('editingMediaId', null)" variant="ghost" size="sm">إلغاء</x-ui.button>
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                @endif
            </x-admin.card>

            {{-- ================= السيو ================= --}}
            <x-admin.card title="تحسين الظهور في البحث" description="اتركها فارغة ليُستخدم العنوان والملخّص تلقائيًا.">
                <div class="grid gap-5">
                    <x-ui.field label="عنوان السيو" :error="$errors->first('seo_title')"
                        hint="ما يظهر كعنوان في نتائج البحث — يُفضّل أن يتضمّن المدينة ونوع الخدمة.">
                        <x-ui.input wire:model="seo_title" placeholder="تصوير حفلات التخرّج في جدة" />
                    </x-ui.field>

                    <x-ui.field label="وصف السيو" :error="$errors->first('seo_description')"
                        hint="سطران يلخّصان الصفحة — هذا ما يقتبسه مساعد الذكاء الاصطناعي غالبًا.">
                        <x-ui.textarea wire:model="seo_description" rows="3" />
                    </x-ui.field>

                    <x-ui.field label="الكلمات المفتاحية" hint="افصل بينها بفاصلة عربية (،)">
                        <x-ui.input wire:model="keywords" placeholder="تصوير تخرّج، مصور جدة، تغطية حفلات" />
                    </x-ui.field>
                </div>
            </x-admin.card>
        </div>

        {{-- ================= العمود الجانبي ================= --}}
        <div class="space-y-6">
            <x-admin.card title="النشر">
                <div class="grid gap-5">
                    <x-ui.field label="الحالة" :error="$errors->first('status')">
                        <x-ui.select wire:model="status">
                            <option value="draft">مسوّدة (غير ظاهرة)</option>
                            <option value="published">منشور</option>
                        </x-ui.select>
                    </x-ui.field>

                    <label class="flex items-center gap-2.5 text-sm cursor-pointer text-ink-700 dark:text-ink-300">
                        <input type="checkbox" wire:model="is_featured"
                            class="border rounded size-4 border-ink-300 text-brand-500 focus:ring-brand-500/40 dark:border-ink-600 dark:bg-ink-800">
                        عنصر مميّز (يظهر في الصفحة الرئيسية)
                    </label>

                    <x-ui.field label="ترتيب العرض" hint="الأصغر يظهر أولًا." :error="$errors->first('sort_order')">
                        <x-ui.input wire:model="sort_order" type="number" min="0" dir="ltr" />
                    </x-ui.field>

                    <div class="grid gap-2 pt-4 border-t border-ink-200 dark:border-ink-800">
                        <x-ui.button type="submit" variant="primary" icon="check" wire:loading.attr="disabled">
                            <span wire:loading.remove wire:target="save">حفظ</span>
                            <span wire:loading wire:target="save">جارٍ الحفظ…</span>
                        </x-ui.button>

                        @if ($status === 'draft')
                            <x-ui.button wire:click="save(true)" variant="brand" icon="upload">حفظ ونشر</x-ui.button>
                        @endif
                    </div>
                </div>
            </x-admin.card>

            <x-admin.card title="التصنيف">
                <div class="grid gap-5">
                    <x-ui.field label="القسم" required :error="$errors->first('section_id')">
                        <x-ui.select wire:model.live="section_id">
                            @foreach ($this->sections as $section)
                                <option value="{{ $section->id }}">{{ $section->name }}</option>
                            @endforeach
                        </x-ui.select>
                    </x-ui.field>

                    @if ($this->categories->isNotEmpty())
                        <x-ui.field label="القسم الفرعي" required :error="$errors->first('category_id')"
                            hint="مطلوب لأن هذا القسم يحتوي أقسامًا فرعية.">
                            <x-ui.select wire:model="category_id" :invalid="$errors->has('category_id')">
                                <option value="">اختر القسم الفرعي</option>
                                @foreach ($this->categories as $category)
                                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                                @endforeach
                            </x-ui.select>
                        </x-ui.field>
                    @endif
                </div>
            </x-admin.card>

            <x-admin.card title="تفاصيل إضافية" description="املأ ما يناسب نوع العنصر فقط.">
                <div class="grid gap-5">
                    <x-ui.field label="الموقع" :error="$errors->first('location')">
                        <x-ui.input wire:model="location" placeholder="{{ config('site.location.city') }}" />
                    </x-ui.field>

                    <x-ui.field label="الجهة أو العميل" :error="$errors->first('client')">
                        <x-ui.input wire:model="client" />
                    </x-ui.field>

                    <x-ui.field label="التاريخ" :error="$errors->first('event_date')">
                        <x-ui.input wire:model="event_date" type="date" />
                    </x-ui.field>

                    <x-ui.field label="المدة" hint="للورش التدريبية" :error="$errors->first('duration')">
                        <x-ui.input wire:model="duration" placeholder="يومان — 8 ساعات" />
                    </x-ui.field>

                    <div class="grid grid-cols-2 gap-4">
                        <x-ui.field label="السعر (ريال)" :error="$errors->first('price')">
                            <x-ui.input wire:model="price" type="number" step="0.01" min="0" dir="ltr" />
                        </x-ui.field>

                        <x-ui.field label="المقاعد" :error="$errors->first('seats')">
                            <x-ui.input wire:model="seats" type="number" min="1" dir="ltr" />
                        </x-ui.field>
                    </div>
                </div>
            </x-admin.card>
        </div>
    </form>
</div>
