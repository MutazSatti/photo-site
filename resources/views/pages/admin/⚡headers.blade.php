<?php

use App\Models\Category;
use App\Models\Media;
use App\Models\Section;
use App\Services\ImageService;
use Illuminate\Support\Facades\Route;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Layout('layouts::admin', ['title' => 'صور الواجهة'])] class extends Component
{
    use WithFileUploads;

    /**
     * الملفات المختارة، مفهرسة بمفتاح الخانة.
     *
     * مصفوفة واحدة لا خاصيّة لكل خانة: عدد الخانات يتغيّر بتغيّر الأقسام في
     * القاعدة، فلا يمكن كتابته في الشيفرة.
     *
     * @var array<string, mixed>
     */
    public array $uploads = [];

    /**
     * كل صورة ثابتة في الموقع، مجموعةً في مجموعاتها.
     *
     * الخانة مفتاحها قيمة usage في جدول الوسائط، وهي العقد الوحيد بينها وبين
     * الصفحة التي تعرضها. و«الافتراضية» ملفٌّ يرحل مع الشيفرة تعود إليه الصفحة
     * إن حُذف المرفوع — لا تملكه إلا واجهات الصفحات.
     *
     * @return array<int, array{title: string, description: string, kind: string, slots: array<int, array{usage: string, label: string, note: string, default: string|null}>}>
     */
    #[Computed]
    public function groups(): array
    {
        return array_values(array_filter([
            [
                'title' => 'واجهات الصفحات',
                'description' => 'الصورة الكبيرة خلف عنوان كل صفحة. لكل صفحة صورة أصلية تصل مع الموقع، وما ترفعه يحلّ محلّها — وحذف رفعك يعيد الأصلية.',
                'kind' => 'cover',
                'slots' => $this->pageSlots(),
            ],
            [
                'title' => 'صفحة التصوير العقاري',
                'description' => 'صفحة الخدمة المخصّصة: صورة واجهتها، وزوج المقارنة «بالجوال / بالكاميرا»، وصور المبادئ الثلاثة.',
                'kind' => 'cover',
                'slots' => $this->fixedSlots([
                    're_hero' => ['صورة الواجهة', 'أقوى لقطة في الملف — تُعرض بعرض الصفحة'],
                    're_before' => ['المقارنة: بالجوال', 'اللقطة نفسها ملتقطة بالجوال قبل التصوير'],
                    're_after' => ['المقارنة: بالكاميرا', 'المشهد نفسه بعد التصوير والمعالجة'],
                    're_craft_verticals' => ['مبدأ: الخطوط الرأسية', 'مساحة بخطوط مستقيمة وتصحيح منظور'],
                    're_craft_bluehour' => ['مبدأ: الساعة الزرقاء', 'لقطة خارجية عند الغروب'],
                    're_craft_styling' => ['مبدأ: التنسيق قبل التصوير', 'ركن مرتَّب قبل اللقطة'],
                ]),
            ],
            [
                'title' => 'شعارات الاعتمادات',
                'description' => 'شعارات الجهات المانحة كما تظهر في صفحة «نبذة». تُعرض على لوحة بيضاء، فالشعار الشفاف أنسبها.',
                'kind' => 'logo',
                'slots' => $this->fixedSlots(collect(accreditations())
                    ->mapWithKeys(fn (array $a): array => [$a['key'] => [$a['authority'], $a['title']]])
                    ->all()),
            ],
        ], fn (array $group): bool => $group['slots'] !== []));
    }

    /**
     * خانات واجهات الصفحات — تُبنى من القاعدة لا من قائمة مكتوبة، فقسمٌ جديد
     * يُنشأ من اللوحة يظهر هنا فورًا وله مكان لصورته.
     *
     * @return array<int, array{usage: string, label: string, note: string, default: string|null}>
     */
    private function pageSlots(): array
    {
        $pages = [
            ['portfolio', 'معرض الأعمال', 'صفحة ثابتة'],
            ['contact', 'التواصل والحجز', 'صفحة ثابتة'],
            ['faq', 'الأسئلة الشائعة', 'صفحة ثابتة'],
            ['about', 'نبذة عني', 'صفحة ثابتة'],
        ];

        foreach (Section::query()->ordered()->get() as $section) {
            $pages[] = [$section->slug, $section->name, 'قسم رئيسي'];
        }

        foreach (Category::query()->with('section:id,name')->ordered()->get() as $category) {
            // الأقسام الفرعية ذات الصفحات المخصّصة (كالتصوير العقاري) لا تمرّ
            // بمكوّن الترويسة العام، فصورةٌ ترفعها لها هنا لن تظهر في الموقع.
            // إخفاؤها أصدق من عرض زرٍّ لا يفعل شيئًا — ولتلك الصفحة مجموعتها.
            if (Route::has('services.'.$category->slug)) {
                continue;
            }

            $pages[] = [$category->slug, $category->name, 'قسم فرعي — '.($category->section->name ?? '')];
        }

        return array_map(fn (array $p): array => [
            'usage' => Media::HEADER_USAGE_PREFIX.$p[0],
            'label' => $p[1],
            'note' => $p[2],
            'default' => header_photo_default($p[0]),
        ], $pages);
    }

    /**
     * خانات ثابتة مكتوبة في الشيفرة — لا صورة أصلية لها، فحذفها يترك فراغًا
     * تتعامل معه صفحتها (بإخفاء القسم غالبًا).
     *
     * @param  array<string, array{0: string, 1: string}>  $definitions
     * @return array<int, array{usage: string, label: string, note: string, default: null}>
     */
    private function fixedSlots(array $definitions): array
    {
        $slots = [];

        foreach ($definitions as $usage => [$label, $note]) {
            $slots[] = ['usage' => $usage, 'label' => $label, 'note' => $note, 'default' => null];
        }

        return $slots;
    }

    /**
     * الصور المرفوعة لكل الخانات، باستعلام واحد.
     *
     * @return array<string, Media>
     */
    #[Computed]
    public function uploaded(): array
    {
        return Media::query()
            ->whereIn('usage', $this->usages())
            ->get()
            ->keyBy(fn (Media $m): string => (string) $m->usage)
            ->all();
    }

    /** @return array<int, string> */
    private function usages(): array
    {
        return array_merge(...array_map(
            fn (array $group): array => array_column($group['slots'], 'usage'),
            $this->groups,
        ));
    }

    public function save(string $usage, ImageService $images): void
    {
        $slot = $this->slot($usage);

        $this->validate([
            'uploads.'.$usage => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:'.config('site.images.max_upload_kb')],
        ], [
            'uploads.'.$usage.'.required' => 'اختر صورة أولًا.',
            'uploads.'.$usage.'.image' => 'الملف يجب أن يكون صورة.',
            'uploads.'.$usage.'.max' => 'حجم الصورة أكبر من المسموح.',
        ]);

        $images->replaceForUsage(
            file: $this->uploads[$usage],
            usage: $usage,
            alt: $slot['label'],
        );

        unset($this->uploads[$usage]);
        $this->refresh();

        $this->dispatch('notify', message: 'حُدّثت الصورة.');
    }

    public function remove(string $usage): void
    {
        $slot = $this->slot($usage);

        Media::where('usage', $usage)->get()->each->delete();

        $this->refresh();

        $this->dispatch('notify', message: $slot['default']
            ? 'حُذفت الصورة المرفوعة، وعادت الصفحة إلى صورتها الأصلية.'
            : 'حُذفت الصورة.');
    }

    public function clearPick(string $usage): void
    {
        unset($this->uploads[$usage]);
    }

    /**
     * الخانة بمفتاحها — والمفتاح يأتي من الواجهة، فلا يُقبل إلا إن كان خانةً
     * معروفة فعلًا.
     *
     * @return array{usage: string, label: string, note: string, default: string|null}
     */
    private function slot(string $usage): array
    {
        foreach ($this->groups as $group) {
            foreach ($group['slots'] as $slot) {
                if ($slot['usage'] === $usage) {
                    return $slot;
                }
            }
        }

        abort(404);
    }

    private function refresh(): void
    {
        Media::forgetHeaders();

        unset($this->uploaded);

        cache()->forget('sync.payload');
        cache()->forget('sync.manifest');
    }
}; ?>

<div>
    <x-admin.page-header
        title="صور الواجهة"
        description="كل صورة ثابتة في الموقع: واجهات الصفحات، وصور صفحة التصوير العقاري، وشعارات الاعتمادات."
    >
        <x-slot:actions>
            {{-- خلفية الرئيسية ليست ترويسةَ صفحة بل واجهةُ الموقع، ومكانها الإعدادات --}}
            <x-ui.button href="{{ route('admin.settings', ['tab' => 'home']) }}" variant="outline" icon="settings">
                خلفية الصفحة الرئيسية
            </x-ui.button>
        </x-slot:actions>
    </x-admin.page-header>

    @foreach ($this->groups as $group)
        <section class="mb-10 last:mb-0">
            <h2 class="text-base font-extrabold text-ink-900 dark:text-ink-100">{{ $group['title'] }}</h2>
            <p class="mt-1 mb-5 text-sm leading-7 text-ink-500 dark:text-ink-400">{{ $group['description'] }}</p>

            <div class="grid gap-5 lg:grid-cols-2">
                @foreach ($group['slots'] as $slot)
                    @php
                        $usage = $slot['usage'];
                        $uploaded = $this->uploaded[$usage] ?? null;
                        $current = $uploaded?->url('md') ?? $slot['default'];
                    @endphp

                    <x-admin.card :title="$slot['label']" :description="$slot['note']">
                        <x-slot:actions>
                            @if ($uploaded)
                                <x-ui.badge variant="brand">مرفوعة</x-ui.badge>
                            @elseif ($slot['default'])
                                <x-ui.badge>الأصلية</x-ui.badge>
                            @else
                                <x-ui.badge variant="warning">بلا صورة</x-ui.badge>
                            @endif
                        </x-slot:actions>

                        @if ($group['kind'] === 'logo')
                            {{-- الشعار يُعرض في الموقع على لوحة بيضاء، فالمعاينة كذلك --}}
                            <div class="flex items-center justify-center p-6 bg-white border rounded-2xl border-ink-200 aspect-21/9 dark:border-ink-700">
                                @if ($current)
                                    <img src="{{ $current }}" alt="" class="object-contain max-w-full max-h-full">
                                @else
                                    <span class="text-xs text-ink-400">لا شعار — يظهر مكانه إطار فارغ</span>
                                @endif
                            </div>
                        @else
                            {{--
                                المعاينة بالتعتيم والتدرّجين أنفسهما اللذين في الترويسة، وبنصّ
                                أبيض في موضعه: الصورة الجميلة قد تبتلع العنوان، ولا يُعرف ذلك
                                من معاينة نظيفة — يُعرف من معاينة تكذب أقلّ ما يمكن.
                            --}}
                            <div class="relative overflow-hidden border rounded-2xl border-ink-200 bg-ink-950 aspect-21/9 dark:border-ink-800">
                                @if ($current)
                                    <img src="{{ $current }}" alt="" class="absolute inset-0 object-cover size-full opacity-65">
                                    <div class="absolute inset-0 bg-gradient-to-t from-ink-950 via-ink-950/70 to-ink-950/25"></div>
                                    <div class="absolute inset-0 bg-gradient-to-l from-ink-950/60 to-transparent"></div>
                                    <div class="absolute inset-y-0 flex items-center px-5 end-0 max-w-[70%]">
                                        <p class="text-base font-extrabold leading-tight text-white text-balance sm:text-lg">{{ $slot['label'] }}</p>
                                    </div>
                                @else
                                    <div class="flex flex-col items-center justify-center gap-2 size-full text-ink-500">
                                        <x-icon name="image" :size="26" />
                                        <span class="text-xs">لا صورة لهذه الخانة</span>
                                    </div>
                                @endif
                            </div>
                        @endif

                        <div class="flex flex-wrap items-start gap-3 mt-4">
                            <label class="flex flex-col items-center justify-center px-5 py-4 transition-colors border border-dashed cursor-pointer grow basis-52 rounded-2xl border-ink-300 hover:border-brand-400 dark:border-ink-700">
                                <input type="file" wire:model="uploads.{{ $usage }}" accept="image/*" class="sr-only">
                                <span class="mb-1.5 text-ink-500 dark:text-ink-400"><x-icon name="upload" :size="20" /></span>
                                <span class="text-sm font-bold text-ink-800 dark:text-ink-200">اختر صورة</span>
                                <span class="mt-1 text-xs text-ink-500 dark:text-ink-400">
                                    {{ $group['kind'] === 'logo' ? 'PNG شفاف يُفضَّل' : 'أفقية وعريضة، 1600 بكسل فأكثر' }}
                                </span>
                            </label>

                            <div class="flex flex-col gap-2">
                                @if (! empty($uploads[$usage]))
                                    <x-ui.button wire:click="save('{{ $usage }}')" icon="check" wire:loading.attr="disabled" wire:target="save('{{ $usage }}')">
                                        <span wire:loading.remove wire:target="save('{{ $usage }}')">حوّل واحفظ</span>
                                        <span wire:loading wire:target="save('{{ $usage }}')">جارٍ التحويل…</span>
                                    </x-ui.button>

                                    <x-ui.button wire:click="clearPick('{{ $usage }}')" variant="ghost" size="sm" icon="close">
                                        إلغاء الاختيار
                                    </x-ui.button>
                                @endif

                                @if ($uploaded)
                                    <x-ui.button
                                        wire:click="remove('{{ $usage }}')"
                                        wire:confirm="{{ $slot['default'] ? 'حذف الصورة المرفوعة والعودة إلى الأصلية؟' : 'حذف هذه الصورة؟' }}"
                                        variant="ghost"
                                        icon="trash"
                                        class="text-red-600 dark:text-red-400"
                                    >
                                        {{ $slot['default'] ? 'استعد الأصلية' : 'حذف الصورة' }}
                                    </x-ui.button>
                                @endif
                            </div>
                        </div>

                        @error('uploads.'.$usage)
                            <p class="mt-3 text-sm font-bold text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </x-admin.card>
                @endforeach
            </div>
        </section>
    @endforeach
</div>
