<?php

use App\Models\Media;
use App\Models\Setting;
use App\Services\ImageService;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Layout('layouts::admin', ['title' => 'الإعدادات'])] class extends Component
{
    use WithFileUploads;

    #[Url(as: 'tab')]
    public string $tab = 'home';

    /** @var array<string, string> */
    public array $values = [];

    public $portrait = null;

    public $hero = null;

    public $logo = null;

    public $favicon = null;

    public $ogImage = null;

    /** الصفحة الجاري تحرير سيو‌ها في تبويب السيو. */
    public string $seoPage = 'home';

    /** @var array<string, string> */
    public array $seoValues = [];

    /** خيارات مظهر الشعار — مجموعة logo، تُصيَّر داخل بطاقة الشعار. */
    public array $logoOpts = [];

    public array $tabs = [
        'home' => ['label' => 'الصفحة الرئيسية', 'icon' => 'home'],
        'general' => ['label' => 'نبذة وأرقام', 'icon' => 'user'],
        'contact' => ['label' => 'التواصل', 'icon' => 'phone'],
        'social' => ['label' => 'التواصل الاجتماعي', 'icon' => 'instagram-solid'],
        'seo' => ['label' => 'السيو', 'icon' => 'search'],
    ];

    public function mount(): void
    {
        $this->loadValues();
        $this->loadLogoOptions();
        $this->loadSeoValues();
    }

    /** المفاتيح التي يمثّلها مربّع اختيار، فتحتاج boolean لا نصًّا. */
    private const BOOL_OPTIONS = ['logo_adapt_dark'];

    /** الصفحات الثابتة القابلة للتحرير، ومفاتيحها ومساراتها. */
    public function seoPages(): array
    {
        return [
            'home' => ['label' => 'الرئيسية', 'route' => 'home', 'icon' => 'home'],
            'portfolio' => ['label' => 'المعرض', 'route' => 'portfolio', 'icon' => 'images'],
            'about' => ['label' => 'النبذة', 'route' => 'about', 'icon' => 'user'],
            'contact' => ['label' => 'التواصل', 'route' => 'contact', 'icon' => 'phone'],
            'faq' => ['label' => 'الأسئلة', 'route' => 'faq', 'icon' => 'help'],
        ];
    }

    /** مفاتيح الإعدادات لصفحة ما — الرئيسية تستخدم المفاتيح العامة. */
    private function seoKeys(string $page): array
    {
        return $page === 'home'
            ? ['title' => 'seo_title', 'description' => 'seo_description']
            : ['title' => "seo_{$page}_title", 'description' => "seo_{$page}_description"];
    }

    private function loadSeoValues(): void
    {
        $keys = $this->seoKeys($this->seoPage);

        $this->seoValues = [
            'title' => (string) Setting::get($keys['title'], ''),
            'description' => (string) Setting::get($keys['description'], ''),
        ];
    }

    public function updatedSeoPage(): void
    {
        $this->loadSeoValues();
        $this->resetErrorBag();
    }

    public function saveSeoPage(): void
    {
        $this->validate([
            'seoValues.title' => ['nullable', 'string', 'max:120'],
            'seoValues.description' => ['nullable', 'string', 'max:320'],
        ], [
            'seoValues.title.max' => 'العنوان طويل — 120 حرفًا كحد أقصى.',
            'seoValues.description.max' => 'الوصف طويل — 320 حرفًا كحد أقصى.',
        ]);

        $keys = $this->seoKeys($this->seoPage);

        Setting::put($keys['title'], $this->seoValues['title'] ?? '');
        Setting::put($keys['description'], $this->seoValues['description'] ?? '');

        $this->flushCaches();

        $this->dispatch('notify', message: 'حُفظ سيو الصفحة.');
    }

    #[Computed]
    public function ogImageMedia(): ?Media
    {
        return Media::where('usage', 'og_default')->first();
    }

    public function saveOgImage(ImageService $images): void
    {
        $this->validate([
            'ogImage' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:'.config('site.images.max_upload_kb')],
        ], [
            'ogImage.required' => 'اختر صورة أولًا.',
            'ogImage.image' => 'الملف يجب أن يكون صورة.',
        ]);

        $images->replaceForUsage(
            file: $this->ogImage,
            usage: 'og_default',
            alt: config('site.owner_name').' — صورة المشاركة',
        );

        $this->reset('ogImage');
        unset($this->ogImageMedia);
        $this->flushCaches();

        $this->dispatch('notify', message: 'حُدّثت صورة المشاركة.');
    }

    public function deleteOgImage(): void
    {
        Media::where('usage', 'og_default')->get()->each->delete();

        unset($this->ogImageMedia);
        $this->flushCaches();

        $this->dispatch('notify', message: 'حُذفت صورة المشاركة.');
    }

    private function loadLogoOptions(): void
    {
        $this->logoOpts = Setting::query()
            ->where('group', 'logo')
            ->pluck('value', 'key')
            ->map(fn ($value, $key) => in_array($key, self::BOOL_OPTIONS, true)
                ? $value === '1'
                : (string) $value)
            ->all();
    }

    private function loadValues(): void
    {
        $this->values = Setting::query()
            ->where('group', $this->tab)
            ->orderBy('sort_order')
            ->pluck('value', 'key')
            ->map(fn ($v) => (string) $v)
            ->all();
    }

    public function updatedTab(): void
    {
        $this->loadValues();
        $this->resetErrorBag();
    }

    #[Computed]
    public function fields()
    {
        return Setting::query()->where('group', $this->tab)->orderBy('sort_order')->get();
    }

    #[Computed]
    public function portraitMedia(): ?Media
    {
        return Media::where('usage', 'owner_portrait')->first();
    }

    #[Computed]
    public function heroMedia(): ?Media
    {
        return Media::where('usage', 'hero')->first();
    }

    #[Computed]
    public function logoMedia(): ?Media
    {
        return Media::where('usage', 'logo')->first();
    }

    #[Computed]
    public function faviconMedia(): ?Media
    {
        return Media::where('usage', 'favicon')->first();
    }

    public function save(): void
    {
        $rules = [];

        foreach ($this->fields as $field) {
            $rules["values.{$field->key}"] = match ($field->type) {
                'number' => ['nullable', 'numeric', 'min:0'],
                'url' => ['nullable', 'url', 'max:300'],
                'textarea' => ['nullable', 'string', 'max:5000'],
                default => ['nullable', 'string', 'max:500'],
            };
        }

        $this->validate($rules, [
            'values.*.url' => 'أدخل رابطًا صحيحًا يبدأ بـ https://',
            'values.*.numeric' => 'أدخل رقمًا صحيحًا.',
        ]);

        foreach ($this->values as $key => $value) {
            Setting::where('key', $key)->update(['value' => $value]);
        }

        Setting::flush();
        $this->flushCaches();

        $this->dispatch('notify', message: 'حُفظت الإعدادات.');
    }

    public function savePortrait(ImageService $images): void
    {
        $this->validate([
            'portrait' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:'.config('site.images.max_upload_kb')],
        ], [
            'portrait.required' => 'اختر صورة أولًا.',
            'portrait.image' => 'الملف يجب أن يكون صورة.',
        ]);

        $images->replaceForUsage(
            file: $this->portrait,
            usage: 'owner_portrait',
            alt: config('site.owner_name').' — '.config('site.job_title').' في '.config('site.location.city'),
        );

        $this->reset('portrait');
        unset($this->portraitMedia);
        $this->flushCaches();

        $this->dispatch('notify', message: 'حُدّثت الصورة الشخصية.');
    }

    public function saveHero(ImageService $images): void
    {
        $this->validate([
            'hero' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:'.config('site.images.max_upload_kb')],
        ], [
            'hero.required' => 'اختر صورة أولًا.',
            'hero.image' => 'الملف يجب أن يكون صورة.',
        ]);

        $images->replaceForUsage(
            file: $this->hero,
            usage: 'hero',
            alt: 'تصوير فعالية في قاعة كبرى — '.config('site.owner_name').'، مصور في '.config('site.location.city'),
        );

        $this->reset('hero');
        unset($this->heroMedia);
        $this->flushCaches();

        $this->dispatch('notify', message: 'حُدّثت صورة الواجهة.');
    }

    public function deleteHero(): void
    {
        Media::where('usage', 'hero')->get()->each->delete();

        unset($this->heroMedia);
        $this->flushCaches();

        $this->dispatch('notify', message: 'حُذفت صورة الواجهة.');
    }

    public function deletePortrait(): void
    {
        Media::where('usage', 'owner_portrait')->get()->each->delete();

        unset($this->portraitMedia);
        $this->flushCaches();

        $this->dispatch('notify', message: 'حُذفت الصورة الشخصية.');
    }

    public function saveLogo(ImageService $images): void
    {
        $this->validate([
            'logo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp,svg', 'max:'.config('site.images.max_upload_kb')],
        ], [
            'logo.required' => 'اختر صورة أولًا.',
            'logo.image' => 'الملف يجب أن يكون صورة.',
        ]);

        $images->replaceForUsage(
            file: $this->logo,
            usage: 'logo',
            alt: config('site.owner_name').' — شعار الموقع',
        );

        $this->reset('logo');
        unset($this->logoMedia);
        $this->flushCaches();

        $this->dispatch('notify', message: 'حُدّث الشعار.');
    }

    public function deleteLogo(): void
    {
        Media::where('usage', 'logo')->get()->each->delete();

        unset($this->logoMedia);
        $this->flushCaches();

        $this->dispatch('notify', message: 'حُذف الشعار — عادت الأيقونة الافتراضية.');
    }

    public function saveLogoOptions(): void
    {
        $this->validate([
            'logoOpts.logo_max_height' => ['required', 'integer', 'min:16', 'max:200'],
            'logoOpts.logo_base_color' => ['required', 'in:black,white'],
            'logoOpts.brand_name' => ['nullable', 'string', 'max:60'],
            'logoOpts.brand_tagline' => ['nullable', 'string', 'max:80'],
            'logoOpts.brand_text_header' => ['required', 'in:both,name,none'],
            'logoOpts.brand_text_footer' => ['required', 'in:both,name,none'],
            'logoOpts.brand_color' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
        ], [
            'logoOpts.logo_max_height.required' => 'حدّد الارتفاع الأقصى.',
            'logoOpts.logo_max_height.min' => 'الارتفاع الأدنى 16 بكسل.',
            'logoOpts.logo_max_height.max' => 'الارتفاع الأقصى 200 بكسل.',
            'logoOpts.brand_name.max' => 'الاسم طويل — 60 حرفًا كحد أقصى.',
            'logoOpts.brand_tagline.max' => 'الوصف طويل — 80 حرفًا كحد أقصى.',
            'logoOpts.brand_color.regex' => 'اللون بصيغة ست خانات مثل #d9861c.',
        ]);

        // مربّع الاختيار يعيد boolean، وبقية الحقول نصوصًا.
        // التخزين موحَّد كنص ليقرأه Setting::get بلا تحويل.
        foreach ($this->logoOpts as $key => $value) {
            Setting::put($key, is_bool($value) ? ($value ? '1' : '0') : (string) $value);
        }

        $this->flushCaches();

        $this->dispatch('notify', message: 'حُفظت خيارات الشعار.');
    }

    public function saveFavicon(ImageService $images): void
    {
        $this->validate([
            'favicon' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:'.config('site.images.max_upload_kb')],
        ], [
            'favicon.required' => 'اختر صورة أولًا.',
            'favicon.image' => 'الملف يجب أن يكون صورة.',
        ]);

        $images->replaceForUsage(
            file: $this->favicon,
            usage: 'favicon',
            alt: config('site.owner_name').' — أيقونة الموقع',
        );

        $this->reset('favicon');
        unset($this->faviconMedia);
        $this->flushCaches();

        $this->dispatch('notify', message: 'حُدّثت أيقونة الموقع.');
    }

    public function deleteFavicon(): void
    {
        Media::where('usage', 'favicon')->get()->each->delete();

        unset($this->faviconMedia);
        $this->flushCaches();

        $this->dispatch('notify', message: 'حُذفت الأيقونة — عادت الأيقونة الافتراضية.');
    }

    private function flushCaches(): void
    {
        Media::forgetLogo();
        cache()->forget('sync.payload');
        cache()->forget('sync.manifest');
        cache()->forget('feed.llms');
    }
}; ?>

<div>
    <x-admin.page-header
        title="إعدادات الموقع"
        description="النصوص والأرقام وبيانات التواصل التي تظهر في الموقع وفي البيانات المهيكلة."
    />

    <div class="grid gap-6 lg:grid-cols-4">

        {{-- ================= التبويبات ================= --}}
        <nav class="lg:col-span-1" aria-label="أقسام الإعدادات">
            <div class="flex gap-2 overflow-x-auto lg:flex-col scrollbar-none">
                @foreach ($tabs as $key => $meta)
                    <button
                        type="button"
                        wire:click="$set('tab', '{{ $key }}')"
                        class="flex shrink-0 items-center gap-2.5 rounded-xl px-4 py-2.5 text-sm font-bold transition-colors lg:w-full {{ $tab === $key
                            ? 'bg-ink-900 text-white dark:bg-brand-500 dark:text-ink-950'
                            : 'text-ink-700 hover:bg-ink-100 dark:text-ink-300 dark:hover:bg-ink-800' }}"
                    >
                        <x-icon :name="$meta['icon']" :size="16" />
                        {{ $meta['label'] }}
                    </button>
                @endforeach
            </div>
        </nav>

        {{-- ================= الحقول ================= --}}
        <div class="space-y-6 lg:col-span-3">
            <x-admin.card :title="$tabs[$tab]['label']">
                <form wire:submit="save" class="grid gap-5">
                    @forelse ($this->fields as $field)
                        <x-ui.field
                            :label="$field->label ?: $field->key"
                            :hint="$field->hint"
                            :error="$errors->first('values.' . $field->key)"
                        >
                            @if ($field->type === 'textarea')
                                <x-ui.textarea
                                    wire:model="values.{{ $field->key }}"
                                    rows="5"
                                    :invalid="$errors->has('values.' . $field->key)"
                                />
                            @elseif ($field->type === 'number')
                                <x-ui.input
                                    wire:model="values.{{ $field->key }}"
                                    type="number"
                                    min="0"
                                    dir="ltr"
                                    :invalid="$errors->has('values.' . $field->key)"
                                />
                            @elseif ($field->type === 'url')
                                <x-ui.input
                                    wire:model="values.{{ $field->key }}"
                                    type="url"
                                    dir="ltr"
                                    :invalid="$errors->has('values.' . $field->key)"
                                />
                            @else
                                <x-ui.input
                                    wire:model="values.{{ $field->key }}"
                                    :dir="str_contains($field->key, 'phone') || str_contains($field->key, 'email') || str_contains($field->key, 'whatsapp') ? 'ltr' : 'rtl'"
                                    :invalid="$errors->has('values.' . $field->key)"
                                />
                            @endif
                        </x-ui.field>
                    @empty
                        <p class="py-8 text-sm text-center text-ink-500 dark:text-ink-400">لا توجد إعدادات في هذا التبويب.</p>
                    @endforelse

                    @if ($this->fields->isNotEmpty())
                        <div class="flex items-center gap-3 pt-2">
                            <x-ui.button type="submit" icon="check" wire:loading.attr="disabled">
                                <span wire:loading.remove wire:target="save">حفظ الإعدادات</span>
                                <span wire:loading wire:target="save">جارٍ الحفظ…</span>
                            </x-ui.button>
                        </div>
                    @endif
                </form>
            </x-admin.card>

            {{-- ================= صورة الواجهة ================= --}}
            @if ($tab === 'home')
                <x-admin.card
                    title="صورة الواجهة"
                    description="الصورة الكبيرة خلف عنوان الصفحة الرئيسية. اختر لقطة أفقية وعميقة — يوضع فوقها تعتيم متدرّج ليبقى النص مقروءًا."
                >
                    <div class="overflow-hidden border rounded-2xl border-ink-200 dark:border-ink-800">
                        <div class="relative bg-ink-900 aspect-21/9">
                            <x-site.picture :media="$this->heroMedia" variant="md" class="size-full" />

                            @if ($this->heroMedia)
                                {{-- معاينة التعتيم كما سيظهر على الموقع فعلًا --}}
                                <div class="absolute inset-0 bg-ink-950/45"></div>
                                <div class="absolute inset-0 bg-gradient-to-l from-ink-950 via-ink-950/70 to-transparent"></div>
                                <div class="absolute inset-y-0 flex items-center px-6 end-0 max-w-md">
                                    <p class="text-lg font-extrabold leading-tight text-white">{{ setting('hero_title') }}</p>
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="flex flex-wrap items-start gap-4 mt-5">
                        <label class="flex grow basis-64 flex-col items-center justify-center px-6 py-6 transition-colors border border-dashed cursor-pointer rounded-2xl border-ink-300 hover:border-brand-400 dark:border-ink-700">
                            <input type="file" wire:model="hero" accept="image/*" class="sr-only">
                            <span class="mb-2 text-ink-500 dark:text-ink-400"><x-icon name="upload" :size="22" /></span>
                            <span class="text-sm font-bold text-ink-800 dark:text-ink-200">اختر صورة الواجهة</span>
                            <span class="mt-1 text-xs text-ink-500 dark:text-ink-400">يُفضّل نسبة أفقية عريضة 16:9 أو أوسع</span>
                        </label>

                        <div class="flex flex-col gap-2">
                            @if ($hero)
                                <x-ui.button wire:click="saveHero" icon="check" wire:loading.attr="disabled">
                                    <span wire:loading.remove wire:target="saveHero">حوّل واحفظ</span>
                                    <span wire:loading wire:target="saveHero">جارٍ التحويل…</span>
                                </x-ui.button>
                            @endif

                            @if ($this->heroMedia)
                                <x-ui.button wire:click="deleteHero" wire:confirm="حذف صورة الواجهة؟"
                                    variant="ghost" icon="trash" class="text-red-600 dark:text-red-400">
                                    حذف الحالية
                                </x-ui.button>
                            @endif
                        </div>
                    </div>

                    @error('hero')
                        <p class="mt-3 text-sm font-bold text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror

                    <div wire:loading wire:target="hero" class="mt-3 text-sm text-ink-500">جارٍ الرفع…</div>
                </x-admin.card>
            @endif

            {{-- ================= شعار الموقع ================= --}}
            @if ($tab === 'general')
                <x-admin.card
                    title="شعار الموقع"
                    description="يظهر في ترويسة كل صفحة وفي التذييل. يُفضّل صورة مربّعة بخلفية شفافة (PNG أو SVG). عند حذفه تعود أيقونة العدسة الافتراضية."
                >
                    <div class="flex flex-wrap items-start gap-6">
                        <div class="flex items-center justify-center border size-40 rounded-2xl border-ink-200 bg-ink-50 dark:border-ink-800 dark:bg-ink-900">
                            @if ($this->logoMedia)
                                <img src="{{ $this->logoMedia->url('thumb') }}" alt="{{ $this->logoMedia->altText() }}"
                                    class="object-contain size-28">
                            @else
                                <span class="flex items-center justify-center size-16 rounded-2xl bg-brand-500 text-ink-950">
                                    <x-icon name="aperture" :size="32" :stroke="2" />
                                </span>
                            @endif
                        </div>

                        <div class="grow basis-64">
                            <label class="flex flex-col items-center justify-center px-6 py-8 transition-colors border border-dashed cursor-pointer rounded-2xl border-ink-300 hover:border-brand-400 dark:border-ink-700">
                                <input type="file" wire:model="logo" accept="image/*" class="sr-only">
                                <span class="mb-2 text-ink-500 dark:text-ink-400"><x-icon name="upload" :size="22" /></span>
                                <span class="text-sm font-bold text-ink-800 dark:text-ink-200">اختر شعارًا</span>
                                <span class="mt-1 text-xs text-ink-500 dark:text-ink-400">مربّع، بخلفية شفافة</span>
                            </label>

                            @error('logo')
                                <p class="mt-2 text-sm font-bold text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror

                            <div wire:loading wire:target="logo" class="mt-2 text-sm text-ink-500">جارٍ الرفع…</div>

                            <div class="flex flex-wrap gap-2 mt-4">
                                @if ($logo)
                                    <x-ui.button wire:click="saveLogo" icon="check" wire:loading.attr="disabled">
                                        <span wire:loading.remove wire:target="saveLogo">حوّل واحفظ</span>
                                        <span wire:loading wire:target="saveLogo">جارٍ التحويل…</span>
                                    </x-ui.button>
                                @endif

                                @if ($this->logoMedia)
                                    <x-ui.button wire:click="deleteLogo" wire:confirm="حذف الشعار والعودة للأيقونة الافتراضية؟"
                                        variant="ghost" icon="trash" class="text-red-600 dark:text-red-400">
                                        حذف الشعار
                                    </x-ui.button>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- خيارات المظهر --}}
                    <div class="pt-6 mt-6 border-t border-ink-200 dark:border-ink-800">
                        <h3 class="mb-4 text-sm font-bold text-ink-900 dark:text-ink-100">مظهر الشعار</h3>

                        <div class="grid gap-5 sm:grid-cols-2">
                            <x-ui.field label="أقصى ارتفاع (بكسل)"
                                hint="يُعرض الشعار بنسبته الطبيعية مقيَّدًا بهذا الارتفاع."
                                :error="$errors->first('logoOpts.logo_max_height')">
                                <x-ui.input wire:model="logoOpts.logo_max_height" type="number" min="16" max="200" dir="ltr"
                                    :invalid="$errors->has('logoOpts.logo_max_height')" />
                            </x-ui.field>

                            <x-ui.field label="لون الشعار الأصلي"
                                hint="يُستخدم لمعرفة متى يحتاج الشعار قلبًا ليظهر على الخلفية.">
                                <select wire:model="logoOpts.logo_base_color"
                                    class="w-full px-4 py-2.5 text-sm font-bold transition-colors border rounded-xl border-ink-300 bg-white text-ink-900 focus:border-brand-500 focus:outline-none dark:border-ink-700 dark:bg-ink-900 dark:text-ink-100">
                                    <option value="black">داكن — يظهر على خلفية فاتحة</option>
                                    <option value="white">فاتح — يظهر على خلفية داكنة</option>
                                </select>
                            </x-ui.field>
                        </div>

                        @if ($this->logoMedia && ! $this->logoMedia->hasTransparency())
                            <div class="flex items-start gap-3 p-4 mt-5 border rounded-xl border-amber-300 bg-amber-50 dark:border-amber-800/60 dark:bg-amber-950/30">
                                <span class="mt-0.5 shrink-0 text-amber-600 dark:text-amber-400"><x-icon name="alert" :size="18" /></span>
                                <div>
                                    <p class="text-sm font-bold text-amber-900 dark:text-amber-200">شعارك بخلفية صلبة لا شفافة</p>
                                    <p class="mt-1 text-xs leading-6 text-amber-800 dark:text-amber-300">
                                        سيظهر مربّعًا على أي خلفية تخالف لون خلفيته، ويتحوّل إلى مربّع صلب
                                        إن فعّلت التكييف مع الوضع الداكن أدناه. ارفع نسخة بصيغة PNG أو SVG
                                        بخلفية شفافة ليظهر الشعار وحده.
                                    </p>
                                </div>
                            </div>
                        @endif

                        <label class="flex items-start gap-3 p-4 mt-5 cursor-pointer rounded-xl bg-ink-50 dark:bg-ink-900">
                            <input type="checkbox" wire:model="logoOpts.logo_adapt_dark"
                                class="mt-0.5 size-4 rounded border-ink-300 text-brand-500 focus:ring-brand-500 dark:border-ink-600">
                            <span>
                                <span class="block text-sm font-bold text-ink-900 dark:text-ink-100">تكييف الشعار مع الوضع الداكن</span>
                                <span class="block mt-1 text-xs text-ink-500 dark:text-ink-400">
                                    يُقلب الشعار إلى أبيض أو أسود خالص حسب الوضع. مناسب للشعارات أحادية اللون — الملوّنة تفقد ألوانها.
                                </span>
                            </span>
                        </label>

                        {{-- النص بجانب الشعار --}}
                        <div class="pt-6 mt-6 border-t border-ink-200 dark:border-ink-800">
                            <h3 class="mb-4 text-sm font-bold text-ink-900 dark:text-ink-100">النص بجانب الشعار</h3>

                            <div class="grid gap-5 sm:grid-cols-2">
                                <x-ui.field label="الاسم" hint="اتركه فارغًا لإخفاء النص كليًا."
                                    :error="$errors->first('logoOpts.brand_name')">
                                    <x-ui.input wire:model="logoOpts.brand_name"
                                        :invalid="$errors->has('logoOpts.brand_name')" />
                                </x-ui.field>

                                <x-ui.field label="الوصف تحت الاسم"
                                    :error="$errors->first('logoOpts.brand_tagline')">
                                    <x-ui.input wire:model="logoOpts.brand_tagline"
                                        :invalid="$errors->has('logoOpts.brand_tagline')" />
                                </x-ui.field>

                                <x-ui.field label="ما يظهر في الترويسة">
                                    <select wire:model="logoOpts.brand_text_header"
                                        class="w-full px-4 py-2.5 text-sm font-bold transition-colors border rounded-xl border-ink-300 bg-white text-ink-900 focus:border-brand-500 focus:outline-none dark:border-ink-700 dark:bg-ink-900 dark:text-ink-100">
                                        <option value="both">الاسم والوصف</option>
                                        <option value="name">الاسم فقط</option>
                                        <option value="none">بلا نص — الشعار وحده</option>
                                    </select>
                                </x-ui.field>

                                <x-ui.field label="ما يظهر في التذييل">
                                    <select wire:model="logoOpts.brand_text_footer"
                                        class="w-full px-4 py-2.5 text-sm font-bold transition-colors border rounded-xl border-ink-300 bg-white text-ink-900 focus:border-brand-500 focus:outline-none dark:border-ink-700 dark:bg-ink-900 dark:text-ink-100">
                                        <option value="both">الاسم والوصف</option>
                                        <option value="name">الاسم فقط</option>
                                        <option value="none">بلا نص — الشعار وحده</option>
                                    </select>
                                </x-ui.field>
                            </div>
                        </div>

                        {{-- اللون الرئيسي --}}
                        <div class="pt-6 mt-6 border-t border-ink-200 dark:border-ink-800">
                            <h3 class="mb-1 text-sm font-bold text-ink-900 dark:text-ink-100">اللون الرئيسي</h3>
                            <p class="mb-4 text-xs text-ink-500 dark:text-ink-400">
                                يسري على الأزرار والروابط والأرقام والشارات في الموقع كله.
                                تُشتق منه بقية الدرجات تلقائيًا. اتركه فارغًا للعودة إلى الذهبي الأصلي.
                            </p>

                            <div class="flex flex-wrap items-end gap-4">
                                <input type="color" wire:model.live="logoOpts.brand_color"
                                    class="border rounded-xl size-14 cursor-pointer border-ink-300 bg-transparent dark:border-ink-700"
                                    aria-label="منتقي اللون">

                                <x-ui.field label="القيمة" hint="بصيغة ست خانات مثل #d9861c"
                                    :error="$errors->first('logoOpts.brand_color')">
                                    <x-ui.input wire:model.live="logoOpts.brand_color" dir="ltr" placeholder="#d9861c"
                                        class="w-36" :invalid="$errors->has('logoOpts.brand_color')" />
                                </x-ui.field>

                                @if (trim((string) ($logoOpts['brand_color'] ?? '')) !== '')
                                    <x-ui.button wire:click="$set('logoOpts.brand_color', '')"
                                        variant="ghost" icon="trash" class="text-ink-600 dark:text-ink-400">
                                        إعادة الأصلي
                                    </x-ui.button>
                                @endif
                            </div>

                            {{-- معاينة التدرّج المشتق --}}
                            @php $preview = trim((string) ($logoOpts['brand_color'] ?? '')) ?: '#d9861c'; @endphp
                            <div class="flex mt-4 overflow-hidden rounded-xl" style="--b:{{ preg_match('/^#[0-9a-fA-F]{6}$/', $preview) ? $preview : '#d9861c' }}">
                                @foreach ([['white',95],['white',88],['white',72],['white',50],['white',25],[null,0],['black',15],['black',32],['black',48],['black',60],['black',78]] as [$mix, $pct])
                                    <span class="h-9 grow"
                                        style="background:{{ $mix ? "color-mix(in oklab, var(--b), {$mix} {$pct}%)" : 'var(--b)' }}"></span>
                                @endforeach
                            </div>
                        </div>

                        <div class="mt-5">
                            <x-ui.button wire:click="saveLogoOptions" icon="check" wire:loading.attr="disabled">
                                <span wire:loading.remove wire:target="saveLogoOptions">حفظ خيارات المظهر</span>
                                <span wire:loading wire:target="saveLogoOptions">جارٍ الحفظ…</span>
                            </x-ui.button>
                        </div>
                    </div>
                </x-admin.card>
            @endif

            {{-- ================= أيقونة الموقع ================= --}}
            @if ($tab === 'general')
                <x-admin.card
                    title="أيقونة الموقع"
                    description="تظهر في تبويب المتصفح وفي المفضّلة وعند إضافة الموقع لشاشة الجوال. يُفضّل صورة مربّعة بسيطة تبقى واضحة بحجم صغير جدًا."
                >
                    <div class="flex flex-wrap items-start gap-6">
                        <div class="flex flex-col items-center gap-3">
                            <div class="flex items-center justify-center border size-24 rounded-2xl border-ink-200 bg-ink-50 dark:border-ink-800 dark:bg-ink-900">
                                @if ($this->faviconMedia)
                                    <img src="{{ $this->faviconMedia->url('thumb') }}" alt="أيقونة الموقع" class="object-contain size-16">
                                @else
                                    <img src="/favicon.svg" alt="الأيقونة الافتراضية" class="object-contain size-16">
                                @endif
                            </div>
                            {{-- معاينة بالحجم الحقيقي في التبويب --}}
                            <div class="flex items-center gap-2 px-3 py-1.5 rounded-lg bg-ink-100 dark:bg-ink-800">
                                <img src="{{ $this->faviconMedia?->url('thumb') ?? '/favicon.svg' }}" alt="" class="object-contain size-4">
                                <span class="text-[11px] text-ink-600 dark:text-ink-400">بحجم التبويب</span>
                            </div>
                        </div>

                        <div class="grow basis-64">
                            <label class="flex flex-col items-center justify-center px-6 py-8 transition-colors border border-dashed cursor-pointer rounded-2xl border-ink-300 hover:border-brand-400 dark:border-ink-700">
                                <input type="file" wire:model="favicon" accept="image/*" class="sr-only">
                                <span class="mb-2 text-ink-500 dark:text-ink-400"><x-icon name="upload" :size="22" /></span>
                                <span class="text-sm font-bold text-ink-800 dark:text-ink-200">اختر أيقونة</span>
                                <span class="mt-1 text-xs text-ink-500 dark:text-ink-400">مربّعة، 512×512 أو أكبر</span>
                            </label>

                            @error('favicon')
                                <p class="mt-2 text-sm font-bold text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror

                            <div wire:loading wire:target="favicon" class="mt-2 text-sm text-ink-500">جارٍ الرفع…</div>

                            <div class="flex flex-wrap gap-2 mt-4">
                                @if ($favicon)
                                    <x-ui.button wire:click="saveFavicon" icon="check" wire:loading.attr="disabled">
                                        <span wire:loading.remove wire:target="saveFavicon">حوّل واحفظ</span>
                                        <span wire:loading wire:target="saveFavicon">جارٍ التحويل…</span>
                                    </x-ui.button>
                                @endif

                                @if ($this->faviconMedia)
                                    <x-ui.button wire:click="deleteFavicon" wire:confirm="حذف الأيقونة والعودة للافتراضية؟"
                                        variant="ghost" icon="trash" class="text-red-600 dark:text-red-400">
                                        حذف الأيقونة
                                    </x-ui.button>
                                @endif
                            </div>

                            <p class="mt-4 text-xs text-ink-500 dark:text-ink-400">
                                قد يحتفظ المتصفح بالأيقونة القديمة في ذاكرته — أعد التحميل بـ Ctrl+Shift+R لرؤية الجديدة.
                            </p>
                        </div>
                    </div>
                </x-admin.card>
            @endif

            {{-- ================= الصورة الشخصية ================= --}}
            @if ($tab === 'general')
                <x-admin.card
                    title="الصورة الشخصية"
                    description="تظهر في صفحة النبذة وتُنشر في بيانات Schema كصورة المصور. تُحوَّل إلى WebP تلقائيًا."
                >
                    <div class="flex flex-wrap items-start gap-6">
                        <div class="overflow-hidden border w-40 rounded-2xl border-ink-200 dark:border-ink-800">
                            <x-site.picture :media="$this->portraitMedia" variant="md" class="w-full aspect-4/5" />
                        </div>

                        <div class="grow basis-64">
                            <label class="flex flex-col items-center justify-center px-6 py-8 transition-colors border border-dashed cursor-pointer rounded-2xl border-ink-300 hover:border-brand-400 dark:border-ink-700">
                                <input type="file" wire:model="portrait" accept="image/*" class="sr-only">
                                <span class="mb-2 text-ink-500 dark:text-ink-400"><x-icon name="upload" :size="22" /></span>
                                <span class="text-sm font-bold text-ink-800 dark:text-ink-200">اختر صورة</span>
                                <span class="mt-1 text-xs text-ink-500 dark:text-ink-400">يُفضّل نسبة عمودية 4:5</span>
                            </label>

                            @error('portrait')
                                <p class="mt-2 text-sm font-bold text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror

                            <div wire:loading wire:target="portrait" class="mt-2 text-sm text-ink-500">جارٍ الرفع…</div>

                            <div class="flex flex-wrap gap-2 mt-4">
                                @if ($portrait)
                                    <x-ui.button wire:click="savePortrait" icon="check" wire:loading.attr="disabled">
                                        <span wire:loading.remove wire:target="savePortrait">حوّل واحفظ</span>
                                        <span wire:loading wire:target="savePortrait">جارٍ التحويل…</span>
                                    </x-ui.button>
                                @endif

                                @if ($this->portraitMedia)
                                    <x-ui.button wire:click="deletePortrait" wire:confirm="حذف الصورة الشخصية؟"
                                        variant="ghost" icon="trash" class="text-red-600 dark:text-red-400">
                                        حذف الحالية
                                    </x-ui.button>
                                @endif
                            </div>
                        </div>
                    </div>
                </x-admin.card>
            @endif

            {{-- ================= ملفات الفهرسة ================= --}}
            @if ($tab === 'seo')
                <x-admin.card
                    title="سيو الصفحات"
                    description="العنوان والوصف اللذان يظهران في نتائج البحث وعند مشاركة الرابط. المعاينة تتحدّث مع كتابتك."
                >
                    {{-- اختيار الصفحة --}}
                    <div class="flex flex-wrap gap-2 mb-6">
                        @foreach ($this->seoPages() as $key => $meta)
                            <button type="button" wire:click="$set('seoPage', '{{ $key }}')"
                                @class([
                                    'flex items-center gap-2 rounded-xl px-4 py-2.5 text-sm font-bold transition-colors',
                                    'bg-brand-500 text-ink-950' => $seoPage === $key,
                                    'bg-ink-100 text-ink-700 hover:bg-ink-150 dark:bg-ink-800 dark:text-ink-300' => $seoPage !== $key,
                                ])>
                                <x-icon :name="$meta['icon']" :size="15" />
                                {{ $meta['label'] }}
                            </button>
                        @endforeach
                    </div>

                    <div class="grid gap-5 mb-6">
                        <x-ui.field label="عنوان الصفحة"
                            hint="يظهر في تبويب المتصفح وكعنوان أزرق في نتائج البحث."
                            :error="$errors->first('seoValues.title')">
                            <x-ui.input wire:model.live.debounce.400ms="seoValues.title"
                                :invalid="$errors->has('seoValues.title')" />
                        </x-ui.field>

                        <x-ui.field label="وصف الصفحة"
                            hint="السطران تحت العنوان. اجعله جملة مكتملة تُغري بالنقر."
                            :error="$errors->first('seoValues.description')">
                            <x-ui.textarea wire:model.live.debounce.400ms="seoValues.description" rows="3"
                                :invalid="$errors->has('seoValues.description')" />
                        </x-ui.field>
                    </div>

                    {{-- المعاينات --}}
                    <x-admin.seo-preview
                        :title="$seoValues['title'] ?? ''"
                        :description="$seoValues['description'] ?? ''"
                        :url="route($this->seoPages()[$seoPage]['route'])"
                        :image="$this->ogImageMedia?->url('md')"
                        :siteName="setting('brand_name', config('site.owner_name'))"
                    />

                    <div class="mt-6">
                        <x-ui.button wire:click="saveSeoPage" icon="check" wire:loading.attr="disabled">
                            <span wire:loading.remove wire:target="saveSeoPage">حفظ سيو الصفحة</span>
                            <span wire:loading wire:target="saveSeoPage">جارٍ الحفظ…</span>
                        </x-ui.button>
                    </div>
                </x-admin.card>

                {{-- ================= صورة المشاركة ================= --}}
                <x-admin.card
                    title="صورة المشاركة"
                    description="تظهر حين يُشارَك رابط الموقع في واتساب أو فيسبوك أو تويتر. المقاس المثالي 1200×630 بنسبة 1.91:1."
                >
                    <div class="flex flex-wrap items-start gap-6">
                        <div class="w-64 overflow-hidden border rounded-xl border-ink-200 dark:border-ink-800">
                            @if ($this->ogImageMedia)
                                <img src="{{ $this->ogImageMedia->url('md') }}" alt="صورة المشاركة"
                                    class="object-cover w-full aspect-[1.91/1]">
                            @else
                                <div class="flex flex-col items-center justify-center gap-2 w-full aspect-[1.91/1] bg-ink-100 dark:bg-ink-800">
                                    <x-icon name="image" :size="26" class="text-ink-400" />
                                    <span class="text-[11px] text-ink-500">تُستخدم صورة الواجهة حاليًا</span>
                                </div>
                            @endif
                        </div>

                        <div class="grow basis-64">
                            <label class="flex flex-col items-center justify-center px-6 py-8 transition-colors border border-dashed cursor-pointer rounded-2xl border-ink-300 hover:border-brand-400 dark:border-ink-700">
                                <input type="file" wire:model="ogImage" accept="image/*" class="sr-only">
                                <span class="mb-2 text-ink-500 dark:text-ink-400"><x-icon name="upload" :size="22" /></span>
                                <span class="text-sm font-bold text-ink-800 dark:text-ink-200">اختر صورة</span>
                                <span class="mt-1 text-xs text-ink-500 dark:text-ink-400">1200×630 بكسل</span>
                            </label>

                            @error('ogImage')
                                <p class="mt-2 text-sm font-bold text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror

                            <div wire:loading wire:target="ogImage" class="mt-2 text-sm text-ink-500">جارٍ الرفع…</div>

                            <div class="flex flex-wrap gap-2 mt-4">
                                @if ($ogImage)
                                    <x-ui.button wire:click="saveOgImage" icon="check" wire:loading.attr="disabled">
                                        <span wire:loading.remove wire:target="saveOgImage">حوّل واحفظ</span>
                                        <span wire:loading wire:target="saveOgImage">جارٍ التحويل…</span>
                                    </x-ui.button>
                                @endif

                                @if ($this->ogImageMedia)
                                    <x-ui.button wire:click="deleteOgImage" wire:confirm="حذف صورة المشاركة؟"
                                        variant="ghost" icon="trash" class="text-red-600 dark:text-red-400">
                                        حذف
                                    </x-ui.button>
                                @endif
                            </div>

                            <p class="mt-4 text-xs leading-6 text-ink-500 dark:text-ink-400">
                                واتساب وفيسبوك يحتفظان بالصورة في ذاكرتهما لأيام. بعد التغيير قد يظل
                                القديم يظهر عند مشاركة الرابط نفسه — جرّب رابطًا بمعامل مختلف للتأكّد.
                            </p>
                        </div>
                    </div>
                </x-admin.card>

                <x-admin.card
                    title="ملفات الزواحف"
                    description="تُولَّد تلقائيًا من محتوى الموقع. افتحها للتأكّد من صحتها بعد أي تعديل كبير."
                >
                    <ul class="grid gap-2 sm:grid-cols-2">
                        @foreach ([
                            ['sitemap', 'خريطة الموقع', 'sitemap.xml — كل الصفحات والصور'],
                            ['feed', 'تغذية RSS', 'feed.xml — أحدث الأعمال'],
                            ['llms', 'ملف llms.txt', 'ملخّص نصي تقرؤه أدوات الذكاء الاصطناعي'],
                            ['robots', 'ملف robots.txt', 'يسمح لزواحف الذكاء الاصطناعي بالوصول'],
                        ] as [$routeName, $label, $desc])
                            <li>
                                <a href="{{ route($routeName) }}" target="_blank" rel="noopener"
                                    class="flex items-center gap-3 p-4 transition-colors border rounded-xl border-ink-200 hover:border-brand-400 dark:border-ink-800 dark:hover:border-brand-600">
                                    <span class="text-ink-400"><x-icon name="document" :size="17" /></span>
                                    <span class="min-w-0">
                                        <span class="block text-sm font-bold text-ink-900 dark:text-ink-100">{{ $label }}</span>
                                        <span class="block mt-0.5 text-xs text-ink-500 dark:text-ink-400">{{ $desc }}</span>
                                    </span>
                                    <span class="text-ink-300 ms-auto dark:text-ink-600"><x-icon name="external-link" :size="15" /></span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </x-admin.card>
            @endif
        </div>
    </div>
</div>
