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
