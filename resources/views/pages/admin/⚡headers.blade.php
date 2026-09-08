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

new #[Layout('layouts::admin', ['title' => 'واجهات الصفحات'])] class extends Component
{
    use WithFileUploads;

    /**
     * الملفات المختارة، مفهرسة بمفتاح الصفحة.
     *
     * مصفوفة واحدة لا خاصيّة لكل صفحة: عدد الصفحات يتغيّر بتغيّر الأقسام في
     * القاعدة، فلا يمكن كتابته في الشيفرة.
     *
     * @var array<string, mixed>
     */
    public array $uploads = [];

    /**
     * صفحات الموقع التي لها ترويسة قابلة للتصوير.
     *
     * الأقسام والأقسام الفرعية تأتي من القاعدة لا من قائمة مكتوبة، فقسمٌ
     * جديد يُنشأ من اللوحة يظهر هنا فورًا وله مكان لصورته.
     *
     * @return array<int, array{key: string, label: string, note: string}>
     */
    #[Computed]
    public function pages(): array
    {
        $pages = [
            ['key' => 'portfolio', 'label' => 'معرض الأعمال', 'note' => 'صفحة ثابتة'],
            ['key' => 'contact', 'label' => 'التواصل والحجز', 'note' => 'صفحة ثابتة'],
            ['key' => 'faq', 'label' => 'الأسئلة الشائعة', 'note' => 'صفحة ثابتة'],
            ['key' => 'about', 'label' => 'نبذة عني', 'note' => 'صفحة ثابتة'],
        ];

        foreach (Section::query()->ordered()->get() as $section) {
            $pages[] = ['key' => $section->slug, 'label' => $section->name, 'note' => 'قسم رئيسي'];
        }

        foreach (Category::query()->with('section:id,name')->ordered()->get() as $category) {
            // الأقسام الفرعية ذات الصفحات المخصّصة (كالتصوير العقاري) لا تمرّ
            // بمكوّن الترويسة العام، فصورةٌ ترفعها لها هنا لن تظهر في الموقع.
            // إخفاؤها أصدق من عرض زرٍّ لا يفعل شيئًا.
            if (Route::has('services.'.$category->slug)) {
                continue;
            }

            $pages[] = [
                'key' => $category->slug,
                'label' => $category->name,
                'note' => 'قسم فرعي — '.($category->section->name ?? ''),
            ];
        }

        return $pages;
    }

    /**
     * الصور المرفوعة، مفهرسة بمفتاح الصفحة.
     *
     * @return array<string, Media>
     */
    #[Computed]
    public function uploaded(): array
    {
        return Media::headers();
    }

    public function save(string $key, ImageService $images): void
    {
        $this->authorizeKey($key);

        $this->validate([
            'uploads.'.$key => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:'.config('site.images.max_upload_kb')],
        ], [
            'uploads.'.$key.'.required' => 'اختر صورة أولًا.',
            'uploads.'.$key.'.image' => 'الملف يجب أن يكون صورة.',
            'uploads.'.$key.'.max' => 'حجم الصورة أكبر من المسموح.',
        ]);

        $images->replaceForUsage(
            file: $this->uploads[$key],
            usage: Media::HEADER_USAGE_PREFIX.$key,
            alt: 'صورة واجهة صفحة '.$this->labelFor($key),
        );

        unset($this->uploads[$key]);
        $this->refresh();

        $this->dispatch('notify', message: 'حُدّثت صورة الواجهة.');
    }

    public function remove(string $key): void
    {
        $this->authorizeKey($key);

        Media::where('usage', Media::HEADER_USAGE_PREFIX.$key)->get()->each->delete();

        $this->refresh();

        $this->dispatch('notify', message: header_photo_default($key)
            ? 'حُذفت الصورة المرفوعة، وعادت الصفحة إلى صورتها الأصلية.'
            : 'حُذفت صورة الواجهة.');
    }

    public function clearPick(string $key): void
    {
        unset($this->uploads[$key]);
    }

    /** المفتاح يأتي من الواجهة، فلا يُقبل إلا إن كان صفحةً معروفة فعلًا. */
    private function authorizeKey(string $key): void
    {
        abort_unless(in_array($key, array_column($this->pages, 'key'), true), 404);
    }

    private function labelFor(string $key): string
    {
        foreach ($this->pages as $page) {
            if ($page['key'] === $key) {
                return $page['label'];
            }
        }

        return $key;
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
        title="واجهات الصفحات"
        description="الصورة الكبيرة خلف عنوان كل صفحة. لكل صفحة صورة أصلية تصل مع الموقع، وما ترفعه هنا يحلّ محلّها — وحذف رفعك يعيد الأصلية."
    >
        <x-slot:actions>
            {{-- خلفية الرئيسية ليست ترويسةَ صفحة بل واجهةُ الموقع، ومكانها الإعدادات --}}
            <x-ui.button href="{{ route('admin.settings', ['tab' => 'home']) }}" variant="outline" icon="settings">
                خلفية الصفحة الرئيسية
            </x-ui.button>
        </x-slot:actions>
    </x-admin.page-header>

    <div class="grid gap-5 lg:grid-cols-2">
        @foreach ($this->pages as $page)
            @php
                $key = $page['key'];
                $uploaded = $this->uploaded[$key] ?? null;
                $default = header_photo_default($key);
                $current = $uploaded?->url('md') ?? $default;
            @endphp

            <x-admin.card :title="$page['label']" :description="$page['note']">
                <x-slot:actions>
                    @if ($uploaded)
                        <x-ui.badge variant="brand">مرفوعة</x-ui.badge>
                    @elseif ($default)
                        <x-ui.badge>الأصلية</x-ui.badge>
                    @else
                        <x-ui.badge variant="warning">بلا صورة</x-ui.badge>
                    @endif
                </x-slot:actions>

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
                            <p class="text-base font-extrabold leading-tight text-white text-balance sm:text-lg">{{ $page['label'] }}</p>
                        </div>
                    @else
                        <div class="flex flex-col items-center justify-center gap-2 size-full text-ink-500">
                            <x-icon name="image" :size="26" />
                            <span class="text-xs">لا صورة لهذه الصفحة — تظهر بالرسم المولَّد</span>
                        </div>
                    @endif
                </div>

                <div class="flex flex-wrap items-start gap-3 mt-4">
                    <label class="flex flex-col items-center justify-center px-5 py-4 transition-colors border border-dashed cursor-pointer grow basis-52 rounded-2xl border-ink-300 hover:border-brand-400 dark:border-ink-700">
                        <input type="file" wire:model="uploads.{{ $key }}" accept="image/*" class="sr-only">
                        <span class="mb-1.5 text-ink-500 dark:text-ink-400"><x-icon name="upload" :size="20" /></span>
                        <span class="text-sm font-bold text-ink-800 dark:text-ink-200">اختر صورة</span>
                        <span class="mt-1 text-xs text-ink-500 dark:text-ink-400">أفقية وعريضة، 1600 بكسل فأكثر</span>
                    </label>

                    <div class="flex flex-col gap-2">
                        @if (! empty($uploads[$key]))
                            <x-ui.button wire:click="save('{{ $key }}')" icon="check" wire:loading.attr="disabled" wire:target="save('{{ $key }}')">
                                <span wire:loading.remove wire:target="save('{{ $key }}')">حوّل واحفظ</span>
                                <span wire:loading wire:target="save('{{ $key }}')">جارٍ التحويل…</span>
                            </x-ui.button>

                            <x-ui.button wire:click="clearPick('{{ $key }}')" variant="ghost" size="sm" icon="close">
                                إلغاء الاختيار
                            </x-ui.button>
                        @endif

                        @if ($uploaded)
                            <x-ui.button
                                wire:click="remove('{{ $key }}')"
                                wire:confirm="{{ $default ? 'حذف الصورة المرفوعة والعودة إلى الأصلية؟' : 'حذف صورة الواجهة؟' }}"
                                variant="ghost"
                                icon="trash"
                                class="text-red-600 dark:text-red-400"
                            >
                                {{ $default ? 'استعد الأصلية' : 'حذف الصورة' }}
                            </x-ui.button>
                        @endif
                    </div>
                </div>

                @error('uploads.'.$key)
                    <p class="mt-3 text-sm font-bold text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </x-admin.card>
        @endforeach
    </div>
</div>
