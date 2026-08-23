<?php

use App\Models\Faq;
use App\Models\Section;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts::admin', ['title' => 'الأسئلة الشائعة'])] class extends Component
{
    public ?int $editingId = null;

    public bool $creating = false;

    public string $question = '';

    public string $answer = '';

    public ?int $section_id = null;

    public string $sort_order = '0';

    public bool $is_active = true;

    #[Computed]
    public function faqs()
    {
        return Faq::query()->ordered()->with('section:id,name')->get();
    }

    #[Computed]
    public function sections()
    {
        return Section::query()->active()->ordered()->get();
    }

    public function create(): void
    {
        $this->resetForm();
        $this->creating = true;
        $this->sort_order = (string) (Faq::max('sort_order') + 1);
    }

    public function edit(int $id): void
    {
        $faq = Faq::findOrFail($id);

        $this->resetForm();
        $this->editingId = $faq->id;
        $this->question = $faq->question;
        $this->answer = $faq->answer;
        $this->section_id = $faq->section_id;
        $this->sort_order = (string) $faq->sort_order;
        $this->is_active = $faq->is_active;
    }

    public function save(): void
    {
        $data = $this->validate([
            'question' => ['required', 'string', 'min:8', 'max:250'],
            'answer' => ['required', 'string', 'min:20', 'max:3000'],
            'section_id' => ['nullable', 'exists:sections,id'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999'],
        ], [], [
            'question' => 'السؤال',
            'answer' => 'الإجابة',
        ]);

        $payload = [
            ...$data,
            'section_id' => $this->section_id ?: null,
            'sort_order' => (int) ($this->sort_order ?: 0),
            'is_active' => $this->is_active,
        ];

        $this->editingId
            ? Faq::findOrFail($this->editingId)->update($payload)
            : Faq::create($payload);

        $this->resetForm();
        unset($this->faqs);
        $this->flushCaches();

        $this->dispatch('notify', message: 'حُفظ السؤال.');
    }

    public function delete(int $id): void
    {
        Faq::findOrFail($id)->delete();

        unset($this->faqs);
        $this->flushCaches();

        $this->dispatch('notify', message: 'حُذف السؤال.');
    }

    public function toggleActive(int $id): void
    {
        $faq = Faq::findOrFail($id);
        $faq->update(['is_active' => ! $faq->is_active]);

        unset($this->faqs);
        $this->flushCaches();
    }

    public function resetForm(): void
    {
        $this->reset(['editingId', 'creating', 'question', 'answer', 'section_id', 'sort_order']);
        $this->is_active = true;
        $this->resetErrorBag();
    }

    private function flushCaches(): void
    {
        cache()->forget('sync.payload');
        cache()->forget('sync.manifest');
        cache()->forget('feed.llms');
    }
}; ?>

<div>
    <x-admin.page-header
        title="الأسئلة الشائعة"
        description="أقوى ما يلتقطه مساعد الذكاء الاصطناعي — كل سؤال يُنشر كبيانات FAQPage مهيكلة. اكتب السؤال كما يطرحه الناس، واجعل الإجابة مكتفية بذاتها."
    >
        <x-slot:actions>
            <x-ui.button wire:click="create" icon="plus">سؤال جديد</x-ui.button>
        </x-slot:actions>
    </x-admin.page-header>

    @if ($creating || $editingId)
        <x-admin.card :title="$editingId ? 'تعديل السؤال' : 'سؤال جديد'" class="mb-6">
            <form wire:submit="save" class="grid gap-5">
                <x-ui.field label="السؤال" required :error="$errors->first('question')"
                    hint="صِغه كما يُكتب في محرك بحث أو يُسأل لمساعد ذكي، مثل: كم يستغرق تسليم الصور؟">
                    <x-ui.input wire:model="question" :invalid="$errors->has('question')" />
                </x-ui.field>

                <x-ui.field label="الإجابة" required :error="$errors->first('answer')"
                    hint="إجابة كاملة تُفهم وحدها دون قراءة بقية الصفحة — هذا ما يجعلها قابلة للاقتباس.">
                    <x-ui.textarea wire:model="answer" rows="6" :invalid="$errors->has('answer')" />
                </x-ui.field>

                <div class="grid gap-4 sm:grid-cols-3">
                    <x-ui.field label="مرتبط بقسم" hint="اختياري">
                        <x-ui.select wire:model="section_id">
                            <option value="">سؤال عام</option>
                            @foreach ($this->sections as $section)
                                <option value="{{ $section->id }}">{{ $section->name }}</option>
                            @endforeach
                        </x-ui.select>
                    </x-ui.field>

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

                <div class="flex gap-2">
                    <x-ui.button type="submit" icon="check">حفظ</x-ui.button>
                    <x-ui.button wire:click="resetForm" variant="ghost">إلغاء</x-ui.button>
                </div>
            </form>
        </x-admin.card>
    @endif

    <x-admin.card :padded="false">
        @if ($this->faqs->isNotEmpty())
            <ul class="divide-y divide-ink-200 dark:divide-ink-800">
                @foreach ($this->faqs as $faq)
                    <li class="flex items-start gap-4 p-4 sm:px-5">
                        <span class="mt-1 text-xs font-extrabold shrink-0 text-ink-400" dir="ltr">{{ $faq->sort_order }}</span>

                        <div class="min-w-0 grow">
                            <div class="flex flex-wrap items-center gap-2">
                                <p class="text-sm font-extrabold text-ink-900 dark:text-ink-100">{{ $faq->question }}</p>
                                @if ($faq->section)
                                    <x-ui.badge>{{ $faq->section->name }}</x-ui.badge>
                                @endif
                            </div>
                            <p class="mt-1.5 text-xs leading-6 text-ink-500 line-clamp-2 dark:text-ink-400">{{ $faq->answer }}</p>
                        </div>

                        <div class="flex items-center gap-1 shrink-0">
                            <button type="button" wire:click="toggleActive({{ $faq->id }})"
                                class="rounded-lg px-2 py-1 text-xs font-bold {{ $faq->is_active
                                    ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-400'
                                    : 'bg-ink-100 text-ink-500 dark:bg-ink-800' }}">
                                {{ $faq->is_active ? 'ظاهر' : 'مخفي' }}
                            </button>

                            <button type="button" wire:click="edit({{ $faq->id }})"
                                class="p-2 transition-colors rounded-lg text-ink-500 hover:bg-ink-100 dark:hover:bg-ink-800"
                                aria-label="تعديل">
                                <x-icon name="pencil" :size="15" />
                            </button>

                            <button type="button" wire:click="delete({{ $faq->id }})" wire:confirm="حذف هذا السؤال؟"
                                class="p-2 text-red-600 transition-colors rounded-lg hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-950/40"
                                aria-label="حذف">
                                <x-icon name="trash" :size="15" />
                            </button>
                        </div>
                    </li>
                @endforeach
            </ul>
        @else
            <p class="px-5 py-16 text-sm text-center text-ink-500 dark:text-ink-400">لم تُضف أسئلة بعد.</p>
        @endif
    </x-admin.card>
</div>
