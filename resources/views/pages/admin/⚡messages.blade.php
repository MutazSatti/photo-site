<?php

use App\Models\ContactMessage;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts::admin', ['title' => 'الرسائل'])] class extends Component
{
    use WithPagination;

    #[Url(as: 'status', except: '')]
    public string $status = '';

    public ?int $openId = null;

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function messages()
    {
        return ContactMessage::query()
            ->when($this->status, fn ($q) => $q->where('status', $this->status))
            ->latest()
            ->paginate(20);
    }

    #[Computed]
    public function counts(): array
    {
        return [
            'all' => ContactMessage::count(),
            'new' => ContactMessage::where('status', 'new')->count(),
            'replied' => ContactMessage::where('status', 'replied')->count(),
            'archived' => ContactMessage::where('status', 'archived')->count(),
        ];
    }

    /** فتح الرسالة يعلّمها مقروءة تلقائيًا. */
    public function open(int $id): void
    {
        if ($this->openId === $id) {
            $this->openId = null;

            return;
        }

        $this->openId = $id;

        $message = ContactMessage::findOrFail($id);

        if ($message->status === 'new') {
            $message->update(['status' => 'read']);
            unset($this->counts);
        }
    }

    public function setStatus(int $id, string $status): void
    {
        ContactMessage::findOrFail($id)->update(['status' => $status]);

        unset($this->counts);

        $this->dispatch('notify', message: 'حُدّثت حالة الرسالة.');
    }

    public function delete(int $id): void
    {
        ContactMessage::findOrFail($id)->delete();

        $this->openId = null;
        unset($this->counts);

        $this->dispatch('notify', message: 'حُذفت الرسالة.');
    }

    /** رابط واتساب جاهز مع تحية باسم المرسل. */
    public function whatsappLink(ContactMessage $message): string
    {
        $number = preg_replace('/\D/', '', $message->phone);

        // الأرقام المحلية تبدأ بصفر — تُحوَّل إلى الصيغة الدولية
        if (str_starts_with($number, '0')) {
            $number = '966'.substr($number, 1);
        }

        $text = "مرحبًا {$message->name}، وصلني طلبك عبر الموقع وأودّ متابعة التفاصيل معك.";

        return 'https://wa.me/'.$number.'?text='.rawurlencode($text);
    }
}; ?>

<div>
    <x-admin.page-header
        title="الرسائل"
        description="طلبات الحجز الواردة من صفحة التواصل."
    />

    {{-- ================= التصفية ================= --}}
    <div class="flex flex-wrap gap-2 mb-5">
        @foreach ([
            '' => ['الكل', $this->counts['all']],
            'new' => ['جديدة', $this->counts['new']],
            'replied' => ['تم الرد', $this->counts['replied']],
            'archived' => ['مؤرشفة', $this->counts['archived']],
        ] as $value => [$label, $count])
            <button
                type="button"
                wire:click="$set('status', '{{ $value }}')"
                class="inline-flex items-center gap-2 rounded-full px-4 py-2 text-sm font-bold transition-colors {{ $status === $value
                    ? 'bg-ink-900 text-white dark:bg-brand-500 dark:text-ink-950'
                    : 'border border-ink-300 text-ink-700 hover:border-ink-400 dark:border-ink-700 dark:text-ink-300' }}"
            >
                {{ $label }}
                <span class="text-xs opacity-70" dir="ltr">{{ $count }}</span>
            </button>
        @endforeach
    </div>

    <x-admin.card :padded="false">
        @if ($this->messages->isNotEmpty())
            <ul class="divide-y divide-ink-200 dark:divide-ink-800">
                @foreach ($this->messages as $message)
                    <li>
                        <button
                            type="button"
                            wire:click="open({{ $message->id }})"
                            class="flex w-full items-start gap-4 p-4 text-right transition-colors sm:px-5 hover:bg-ink-50 dark:hover:bg-ink-800/50"
                        >
                            <span @class([
                                'mt-2 size-2 shrink-0 rounded-full',
                                'bg-brand-500' => $message->status === 'new',
                                'bg-ink-300 dark:bg-ink-700' => $message->status !== 'new',
                            ])></span>

                            <span class="min-w-0 grow">
                                <span class="flex flex-wrap items-center gap-2">
                                    <span class="text-sm font-extrabold text-ink-900 dark:text-ink-100">{{ $message->name }}</span>
                                    <span class="text-xs text-ink-500" dir="ltr">{{ $message->phone }}</span>
                                    @if ($message->service)
                                        <x-ui.badge variant="brand">{{ $message->service }}</x-ui.badge>
                                    @endif
                                </span>

                                <span class="block mt-1 text-xs leading-6 text-ink-500 line-clamp-1 dark:text-ink-400">
                                    {{ $message->message }}
                                </span>
                            </span>

                            <span class="flex flex-col items-end gap-1 shrink-0">
                                <span class="text-xs text-ink-400">{{ $message->created_at->diffForHumans(short: true) }}</span>
                                <x-ui.badge :variant="match ($message->status) {
                                    'new' => 'brand',
                                    'replied' => 'success',
                                    'archived' => 'neutral',
                                    default => 'neutral',
                                }">{{ $message->statusLabel() }}</x-ui.badge>
                            </span>
                        </button>

                        {{-- تفاصيل الرسالة --}}
                        @if ($openId === $message->id)
                            <div class="px-4 pb-5 sm:px-5">
                                <div class="p-5 bg-ink-50 rounded-2xl dark:bg-ink-950/50">
                                    <dl class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                                        <div>
                                            <dt class="text-xs text-ink-500 dark:text-ink-400">الاسم</dt>
                                            <dd class="mt-0.5 text-sm font-bold text-ink-900 dark:text-ink-100">{{ $message->name }}</dd>
                                        </div>
                                        <div>
                                            <dt class="text-xs text-ink-500 dark:text-ink-400">الجوال</dt>
                                            <dd class="mt-0.5 text-sm font-bold text-ink-900 dark:text-ink-100" dir="ltr">{{ $message->phone }}</dd>
                                        </div>
                                        @if ($message->email)
                                            <div>
                                                <dt class="text-xs text-ink-500 dark:text-ink-400">البريد</dt>
                                                <dd class="mt-0.5 truncate text-sm font-bold text-ink-900 dark:text-ink-100" dir="ltr">{{ $message->email }}</dd>
                                            </div>
                                        @endif
                                        @if ($message->event_date)
                                            <div>
                                                <dt class="text-xs text-ink-500 dark:text-ink-400">تاريخ المناسبة</dt>
                                                <dd class="mt-0.5 text-sm font-bold text-ink-900 dark:text-ink-100">{{ $message->event_date->translatedFormat('j F Y') }}</dd>
                                            </div>
                                        @endif
                                    </dl>

                                    <div class="pt-4 mt-4 border-t border-ink-200 dark:border-ink-800">
                                        <p class="text-xs text-ink-500 dark:text-ink-400">نص الرسالة</p>
                                        <p class="mt-2 text-sm leading-8 whitespace-pre-line text-ink-800 dark:text-ink-200">{{ $message->message }}</p>
                                    </div>

                                    <div class="flex flex-wrap gap-2 pt-4 mt-4 border-t border-ink-200 dark:border-ink-800">
                                        <x-ui.button href="{{ $this->whatsappLink($message) }}" variant="whatsapp" size="sm" icon="whatsapp"
                                            :navigate="false" target="_blank" rel="noopener">
                                            رد على الواتساب
                                        </x-ui.button>

                                        <x-ui.button href="tel:{{ $message->phone }}" variant="outline" size="sm" icon="phone" :navigate="false">
                                            اتصال
                                        </x-ui.button>

                                        @if ($message->email)
                                            <x-ui.button href="mailto:{{ $message->email }}" variant="outline" size="sm" icon="mail" :navigate="false">
                                                بريد
                                            </x-ui.button>
                                        @endif

                                        <div class="flex gap-2 ms-auto">
                                            @if ($message->status !== 'replied')
                                                <x-ui.button wire:click="setStatus({{ $message->id }}, 'replied')" variant="ghost" size="sm" icon="check">
                                                    تم الرد
                                                </x-ui.button>
                                            @endif

                                            @if ($message->status !== 'archived')
                                                <x-ui.button wire:click="setStatus({{ $message->id }}, 'archived')" variant="ghost" size="sm">
                                                    أرشفة
                                                </x-ui.button>
                                            @endif

                                            <x-ui.button wire:click="delete({{ $message->id }})" wire:confirm="حذف هذه الرسالة نهائيًا؟"
                                                variant="ghost" size="sm" icon="trash" class="text-red-600 dark:text-red-400">
                                                حذف
                                            </x-ui.button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </li>
                @endforeach
            </ul>

            @if ($this->messages->hasPages())
                <div class="px-5 py-4 border-t border-ink-200 dark:border-ink-800">
                    {{ $this->messages->links() }}
                </div>
            @endif
        @else
            <p class="px-5 py-16 text-sm text-center text-ink-500 dark:text-ink-400">لا توجد رسائل في هذا التصنيف.</p>
        @endif
    </x-admin.card>
</div>
