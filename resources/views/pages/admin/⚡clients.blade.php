<?php

use App\Models\Client;
use App\Services\ImageService;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Layout('layouts::admin', ['title' => 'الجهات والعملاء'])] class extends Component
{
    use WithFileUploads;

    public ?int $editingId = null;

    public bool $creating = false;

    public string $name = '';

    public string $name_en = '';

    public string $url = '';

    public string $sort_order = '0';

    public bool $is_active = true;

    /** ملف الشعار المرفوع قبل تحويله — يبقى فارغًا ما لم يختر المالك ملفًا. */
    public $logo = null;

    #[Computed]
    public function clients()
    {
        return Client::query()->ordered()->with('logo')->get();
    }

    /** الجهة قيد التعديل — تُقرأ من جديد ليظهر الشعار فور استبداله أو حذفه. */
    #[Computed]
    public function editingClient(): ?Client
    {
        return $this->editingId ? Client::with('logo')->find($this->editingId) : null;
    }

    #[Computed]
    public function webpReady(): bool
    {
        return ImageService::webpSupported();
    }

    public function create(): void
    {
        $this->resetForm();
        $this->creating = true;
        $this->sort_order = (string) (Client::max('sort_order') + 1);
    }

    public function edit(int $id): void
    {
        $client = Client::findOrFail($id);

        $this->resetForm();
        $this->editingId = $client->id;
        $this->name = $client->name;
        $this->name_en = (string) $client->name_en;
        $this->url = (string) $client->url;
        $this->sort_order = (string) $client->sort_order;
        $this->is_active = $client->is_active;
    }

    public function save(ImageService $images): void
    {
        $data = $this->validate([
            'name' => ['required', 'string', 'max:120'],
            'name_en' => ['nullable', 'string', 'max:120'],
            'url' => ['nullable', 'url', 'max:300'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999'],
            'logo' => ['nullable', 'image', 'mimes:png,webp,jpg,jpeg', 'max:'.config('site.images.max_upload_kb')],
        ], [
            'logo.image' => 'الملف يجب أن يكون صورة.',
            'logo.mimes' => 'الصيغ المقبولة: PNG أو WebP أو JPG.',
        ], [
            'name' => 'اسم الجهة',
            'url' => 'رابط الموقع',
            'logo' => 'الشعار',
        ]);

        $payload = [
            'name' => $data['name'],
            'name_en' => $this->name_en ?: null,
            'url' => $this->url ?: null,
            'sort_order' => (int) ($this->sort_order ?: 0),
            'is_active' => $this->is_active,
        ];

        $client = $this->editingId
            ? tap(Client::findOrFail($this->editingId))->update($payload)
            : Client::create($payload);

        if ($this->logo) {
            // الشعار السابق يُحذف بعد نجاح تحويل الجديد لا قبله، فلا تفقد الجهة
            // شعارها إن انقطع التحويل في منتصفه
            $previous = $client->logo;

            $media = $images->store(
                file: $this->logo,
                usage: Client::LOGO_USAGE,
                alt: $client->logoAlt(),
            );

            $client->update(['media_id' => $media->id]);
            $client->setRelation('logo', $media);

            $previous?->delete();
        }

        // اسم الجهة هو النص البديل لشعارها، فتعديل الاسم يجب أن يتبعه.
        // القيمة المطابقة لا تُنتج استعلامًا لأن النموذج لا يصير متّسخًا.
        $client->logo?->update(['alt' => $client->logoAlt()]);

        $this->resetForm();
        unset($this->clients, $this->editingClient);
        $this->flushCaches();

        $this->dispatch('notify', message: 'حُفظت الجهة.');
    }

    public function deleteLogo(int $id): void
    {
        $client = Client::findOrFail($id);

        $client->logo?->delete();
        $client->update(['media_id' => null]);

        unset($this->clients, $this->editingClient);
        $this->flushCaches();

        $this->dispatch('notify', message: 'حُذف الشعار.');
    }

    public function delete(int $id): void
    {
        Client::findOrFail($id)->delete();

        if ($this->editingId === $id) {
            $this->resetForm();
        }

        unset($this->clients, $this->editingClient);
        $this->flushCaches();

        $this->dispatch('notify', message: 'حُذفت الجهة.');
    }

    public function toggleActive(int $id): void
    {
        $client = Client::findOrFail($id);
        $client->update(['is_active' => ! $client->is_active]);

        unset($this->clients);
        $this->flushCaches();
    }

    /** ينقل جهة خطوة واحدة، ثم يعيد ترقيم الجميع من الصفر. */
    public function move(int $id, string $direction): void
    {
        $clients = Client::query()->ordered()->get()->values();
        $from = $clients->search(fn (Client $c) => $c->id === $id);

        if ($from === false) {
            return;
        }

        $to = $direction === 'up' ? $from - 1 : $from + 1;

        if ($to < 0 || $to >= $clients->count()) {
            return;
        }

        $reordered = $clients->all();
        [$reordered[$from], $reordered[$to]] = [$reordered[$to], $reordered[$from]];

        // إعادة الترقيم من الصفر تُصلح أي تكرار قديم في الترتيب بدل أن تبني عليه
        foreach ($reordered as $position => $client) {
            if ($client->sort_order !== $position) {
                $client->update(['sort_order' => $position]);
            }
        }

        unset($this->clients);
        $this->flushCaches();
    }

    public function resetForm(): void
    {
        $this->reset(['editingId', 'creating', 'name', 'name_en', 'url', 'sort_order', 'logo']);
        $this->is_active = true;
        $this->resetErrorBag();
    }

    private function flushCaches(): void
    {
        cache()->forget('sync.payload');
        cache()->forget('sync.manifest');
    }
}; ?>

<div>
    <x-admin.page-header
        title="الجهات والعملاء"
        description="شعارات الجهات التي تعاملت معها تُعرض في الصفحة الرئيسية. تُوحَّد معالجتها اللونية لتتناسق مع الموقع، ويكشف المرور بالمؤشّر ألوان الشعار الأصلية."
    >
        <x-slot:actions>
            <x-ui.button wire:click="create" icon="plus">جهة جديدة</x-ui.button>
        </x-slot:actions>
    </x-admin.page-header>

    @unless ($this->webpReady)
        <x-ui.alert variant="warning" class="mb-6">
            بيئة الخادم الحالية لا تدعم تحويل الصور إلى WebP، فلن يعمل رفع الشعارات حتى تُفعَّل إضافة GD أو Imagick.
        </x-ui.alert>
    @endunless

    @if ($creating || $editingId)
        <x-admin.card :title="$editingId ? 'تعديل الجهة' : 'جهة جديدة'" class="mb-6">
            <form wire:submit="save" class="grid gap-5">
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-ui.field label="اسم الجهة" required :error="$errors->first('name')">
                        <x-ui.input wire:model="name" :invalid="$errors->has('name')" placeholder="غرفة تجارة المدينة" />
                    </x-ui.field>

                    <x-ui.field
                        label="الاسم بالإنجليزية"
                        :error="$errors->first('name_en')"
                        hint="اختياري — يُعرض في لوحة التحكم لتمييز الجهات المتشابهة أسماؤها."
                    >
                        <x-ui.input wire:model="name_en" dir="ltr" placeholder="Chamber of Commerce" />
                    </x-ui.field>
                </div>

                <x-ui.field
                    label="رابط موقع الجهة"
                    :error="$errors->first('url')"
                    hint="عند إدخاله يصبح الشعار رابطًا يفتح في تبويب جديد. والجهة بلا موقع إلكتروني يُوضع لها رابط موقعها على خرائط Google بدلًا منه."
                >
                    <x-ui.input wire:model="url" type="url" dir="ltr" placeholder="https://example.sa" />
                </x-ui.field>

                <x-ui.field
                    label="الشعار"
                    :error="$errors->first('logo')"
                    hint="يُفضّل PNG أو WebP بخلفية شفافة أو بيضاء. بطاقة الشعار فاتحة في الوضعين الفاتح والداكن، فلا يحتاج الشعار نسخةً خاصة بالوضع الداكن."
                >
                    <div class="flex flex-wrap items-center gap-4">
                        <div class="flex items-center justify-center p-3 bg-white border rounded-2xl size-24 shrink-0 border-ink-200 dark:border-ink-300 dark:bg-ink-100">
                            @if ($logo)
                                <img src="{{ $logo->temporaryUrl() }}" alt="معاينة الشعار" class="object-contain size-full">
                            @elseif ($this->editingClient?->logo)
                                <x-site.picture :media="$this->editingClient->logo" variant="thumb" fit="contain" class="size-full" />
                            @else
                                <span class="text-ink-300 dark:text-ink-600"><x-icon name="building" :size="28" /></span>
                            @endif
                        </div>

                        <div class="flex flex-col items-start gap-2">
                            <label class="inline-flex cursor-pointer items-center gap-2 rounded-xl border border-dashed border-ink-300 px-4 py-2.5 text-sm font-bold text-ink-800 transition-colors hover:border-brand-400 dark:border-ink-700 dark:text-ink-200">
                                <input type="file" wire:model="logo" accept="image/png,image/webp,image/jpeg" class="sr-only">
                                <x-icon name="upload" :size="16" />
                                {{ $this->editingClient?->logo ? 'استبدال الشعار' : 'اختر ملف الشعار' }}
                            </label>

                            @if ($this->editingClient?->logo)
                                <button type="button" wire:click="deleteLogo({{ $this->editingClient->id }})" wire:confirm="حذف شعار هذه الجهة؟"
                                    class="inline-flex items-center gap-1.5 text-xs font-bold text-red-600 dark:text-red-400">
                                    <x-icon name="trash" :size="14" />
                                    حذف الشعار الحالي
                                </button>
                            @endif

                            <span wire:loading wire:target="logo" class="text-xs text-ink-500">جارٍ الرفع…</span>
                        </div>
                    </div>
                </x-ui.field>

                <div class="grid gap-4 sm:grid-cols-2">
                    <x-ui.field label="الترتيب" :error="$errors->first('sort_order')">
                        <x-ui.input wire:model="sort_order" type="number" min="0" dir="ltr" />
                    </x-ui.field>

                    <x-ui.field label="الظهور">
                        <label class="inline-flex items-center gap-2 py-2.5 text-sm cursor-pointer text-ink-700 dark:text-ink-300">
                            <input type="checkbox" wire:model="is_active"
                                class="border rounded size-4 border-ink-300 text-brand-500 dark:border-ink-600 dark:bg-ink-800">
                            ظاهر في الصفحة الرئيسية
                        </label>
                    </x-ui.field>
                </div>

                <div class="flex gap-2">
                    <x-ui.button type="submit" icon="check" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="save">حفظ</span>
                        <span wire:loading wire:target="save">جارٍ الحفظ…</span>
                    </x-ui.button>
                    <x-ui.button wire:click="resetForm" variant="ghost">إلغاء</x-ui.button>
                </div>
            </form>
        </x-admin.card>
    @endif

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @forelse ($this->clients as $index => $client)
            <div data-client="{{ $client->id }}" class="flex flex-col p-5 bg-white border rounded-2xl border-ink-200 dark:border-ink-800 dark:bg-ink-900">
                <div class="flex items-start gap-4">
                    <div class="flex items-center justify-center p-2 bg-white border rounded-xl size-16 shrink-0 border-ink-200 dark:border-ink-300 dark:bg-ink-100">
                        @if ($client->logo)
                            <x-site.picture :media="$client->logo" variant="thumb" fit="contain" class="size-full" />
                        @else
                            <span class="text-ink-300 dark:text-ink-600"><x-icon name="building" :size="22" /></span>
                        @endif
                    </div>

                    <div class="min-w-0 grow">
                        <p class="text-sm font-extrabold truncate text-ink-900 dark:text-ink-100">{{ $client->name }}</p>

                        @if ($client->name_en)
                            <p class="text-xs truncate text-ink-500 dark:text-ink-400" dir="ltr">{{ $client->name_en }}</p>
                        @endif

                        @if ($client->url)
                            <a href="{{ $client->url }}" target="_blank" rel="noopener nofollow"
                                class="mt-1 inline-flex items-center gap-1 text-xs font-bold text-brand-600 dark:text-brand-400">
                                <x-icon name="external-link" :size="12" />
                                زيارة الموقع
                            </a>
                        @endif
                    </div>

                    <button type="button" wire:click="toggleActive({{ $client->id }})"
                        class="shrink-0 rounded-lg px-2 py-0.5 text-xs font-bold {{ $client->is_active
                            ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-400'
                            : 'bg-ink-100 text-ink-500 dark:bg-ink-800' }}">
                        {{ $client->is_active ? 'ظاهر' : 'مخفي' }}
                    </button>
                </div>

                @unless ($client->logo)
                    <p class="mt-4 text-xs text-amber-700 dark:text-amber-500">
                        بلا شعار — تُعرض الجهة باسمها في الصفحة الرئيسية.
                    </p>
                @endunless

                <div class="flex items-center justify-between gap-2 pt-4 mt-4 border-t border-ink-200 dark:border-ink-800">
                    <div class="flex gap-1">
                        <button type="button" wire:click="move({{ $client->id }}, 'up')" @disabled($index === 0)
                            class="p-2 transition-colors rounded-lg text-ink-500 hover:bg-ink-100 disabled:opacity-30 dark:hover:bg-ink-800"
                            aria-label="تقديم">
                            <x-icon name="chevron-up" :size="14" />
                        </button>

                        <button type="button" wire:click="move({{ $client->id }}, 'down')" @disabled($index === $this->clients->count() - 1)
                            class="p-2 transition-colors rounded-lg text-ink-500 hover:bg-ink-100 disabled:opacity-30 dark:hover:bg-ink-800"
                            aria-label="تأخير">
                            <x-icon name="chevron-down" :size="14" />
                        </button>
                    </div>

                    <div class="flex gap-1">
                        <button type="button" wire:click="edit({{ $client->id }})"
                            class="p-2 transition-colors rounded-lg text-ink-500 hover:bg-ink-100 dark:hover:bg-ink-800"
                            aria-label="تعديل">
                            <x-icon name="pencil" :size="14" />
                        </button>

                        <button type="button" wire:click="delete({{ $client->id }})" wire:confirm="حذف هذه الجهة وشعارها؟"
                            class="p-2 text-red-600 transition-colors rounded-lg hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-950/40"
                            aria-label="حذف">
                            <x-icon name="trash" :size="14" />
                        </button>
                    </div>
                </div>
            </div>
        @empty
            <div class="sm:col-span-2 lg:col-span-3">
                <x-admin.card>
                    <p class="py-12 text-sm text-center text-ink-500 dark:text-ink-400">
                        لم تُضف جهات بعد — قسم الشعارات لا يظهر في الصفحة الرئيسية حتى تُضاف أول جهة.
                    </p>
                </x-admin.card>
            </div>
        @endforelse
    </div>
</div>
