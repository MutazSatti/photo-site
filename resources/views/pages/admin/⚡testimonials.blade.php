<?php

use App\Models\Testimonial;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts::admin', ['title' => 'آراء العملاء'])] class extends Component
{
    public ?int $editingId = null;

    public bool $creating = false;

    public string $name = '';

    public string $role = '';

    public string $content = '';

    public int $rating = 5;

    public string $sort_order = '0';

    public bool $is_active = true;

    #[Computed]
    public function testimonials()
    {
        return Testimonial::query()->ordered()->get();
    }

    public function create(): void
    {
        $this->resetForm();
        $this->creating = true;
        $this->sort_order = (string) (Testimonial::max('sort_order') + 1);
    }

    public function edit(int $id): void
    {
        $item = Testimonial::findOrFail($id);

        $this->resetForm();
        $this->editingId = $item->id;
        $this->name = $item->name;
        $this->role = (string) $item->role;
        $this->content = $item->content;
        $this->rating = $item->rating;
        $this->sort_order = (string) $item->sort_order;
        $this->is_active = $item->is_active;
    }

    public function save(): void
    {
        $data = $this->validate([
            'name' => ['required', 'string', 'max:120'],
            'role' => ['nullable', 'string', 'max:120'],
            'content' => ['required', 'string', 'min:15', 'max:1000'],
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999'],
        ], [], [
            'name' => 'الاسم',
            'content' => 'نص الرأي',
            'rating' => 'التقييم',
        ]);

        $payload = [
            ...$data,
            'role' => $this->role ?: null,
            'sort_order' => (int) ($this->sort_order ?: 0),
            'is_active' => $this->is_active,
        ];

        $this->editingId
            ? Testimonial::findOrFail($this->editingId)->update($payload)
            : Testimonial::create($payload);

        $this->resetForm();
        unset($this->testimonials);
        $this->flushCaches();

        $this->dispatch('notify', message: 'حُفظ الرأي.');
    }

    public function delete(int $id): void
    {
        Testimonial::findOrFail($id)->delete();

        unset($this->testimonials);
        $this->flushCaches();

        $this->dispatch('notify', message: 'حُذف الرأي.');
    }

    public function toggleActive(int $id): void
    {
        $item = Testimonial::findOrFail($id);
        $item->update(['is_active' => ! $item->is_active]);

        unset($this->testimonials);
        $this->flushCaches();
    }

    public function resetForm(): void
    {
        $this->reset(['editingId', 'creating', 'name', 'role', 'content', 'sort_order']);
        $this->rating = 5;
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
        title="آراء العملاء"
        description="تُنشر كتقييم إجمالي في البيانات المهيكلة. انشر آراء حقيقية فقط — التقييمات المهيكلة يجب أن تكون صادقة."
    >
        <x-slot:actions>
            <x-ui.button wire:click="create" icon="plus">رأي جديد</x-ui.button>
        </x-slot:actions>
    </x-admin.page-header>

    @if ($creating || $editingId)
        <x-admin.card :title="$editingId ? 'تعديل الرأي' : 'رأي جديد'" class="mb-6">
            <form wire:submit="save" class="grid gap-5">
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-ui.field label="اسم العميل" required :error="$errors->first('name')">
                        <x-ui.input wire:model="name" :invalid="$errors->has('name')" />
                    </x-ui.field>

                    <x-ui.field label="الصفة أو جهة العمل" :error="$errors->first('role')">
                        <x-ui.input wire:model="role" placeholder="منظّم فعاليات" />
                    </x-ui.field>
                </div>

                <x-ui.field label="نص الرأي" required :error="$errors->first('content')">
                    <x-ui.textarea wire:model="content" rows="4" :invalid="$errors->has('content')" />
                </x-ui.field>

                <div class="grid gap-4 sm:grid-cols-3">
                    <x-ui.field label="التقييم" :error="$errors->first('rating')">
                        <div class="flex items-center gap-1">
                            @for ($i = 1; $i <= 5; $i++)
                                <button type="button" wire:click="$set('rating', {{ $i }})"
                                    class="p-1 transition-colors {{ $i <= $rating ? 'text-brand-500' : 'text-ink-300 dark:text-ink-600' }}"
                                    aria-label="{{ $i }} من 5">
                                    <x-icon :name="$i <= $rating ? 'star-filled' : 'star'" :size="20" />
                                </button>
                            @endfor
                        </div>
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

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @forelse ($this->testimonials as $item)
            <div class="flex flex-col p-5 bg-white border rounded-2xl border-ink-200 dark:border-ink-800 dark:bg-ink-900">
                <div class="flex items-center justify-between gap-2">
                    <div class="flex gap-0.5 text-brand-500">
                        @for ($i = 0; $i < $item->rating; $i++)
                            <x-icon name="star-filled" :size="14" />
                        @endfor
                    </div>

                    <button type="button" wire:click="toggleActive({{ $item->id }})"
                        class="rounded-lg px-2 py-0.5 text-xs font-bold {{ $item->is_active
                            ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-400'
                            : 'bg-ink-100 text-ink-500 dark:bg-ink-800' }}">
                        {{ $item->is_active ? 'ظاهر' : 'مخفي' }}
                    </button>
                </div>

                <p class="mt-3 text-sm leading-7 text-ink-700 grow dark:text-ink-300">{{ $item->content }}</p>

                <div class="flex items-center justify-between gap-2 pt-4 mt-4 border-t border-ink-200 dark:border-ink-800">
                    <div class="min-w-0">
                        <p class="text-sm font-extrabold truncate text-ink-900 dark:text-ink-100">{{ $item->name }}</p>
                        @if ($item->role)
                            <p class="text-xs truncate text-ink-500 dark:text-ink-400">{{ $item->role }}</p>
                        @endif
                    </div>

                    <div class="flex gap-1 shrink-0">
                        <button type="button" wire:click="edit({{ $item->id }})"
                            class="p-2 transition-colors rounded-lg text-ink-500 hover:bg-ink-100 dark:hover:bg-ink-800"
                            aria-label="تعديل">
                            <x-icon name="pencil" :size="14" />
                        </button>

                        <button type="button" wire:click="delete({{ $item->id }})" wire:confirm="حذف هذا الرأي؟"
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
                    <p class="py-12 text-sm text-center text-ink-500 dark:text-ink-400">لم تُضف آراء بعد.</p>
                </x-admin.card>
            </div>
        @endforelse
    </div>
</div>
