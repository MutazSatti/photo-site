<?php

use App\Concerns\PasswordValidationRules;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Laravel\Fortify\Features;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts::admin', ['title' => 'الأمان'])] class extends Component
{
    use PasswordValidationRules;

    public string $current_password = '';

    public string $password = '';

    public string $password_confirmation = '';

    public function mount(): void
    {
        $this->discardAbandonedTwoFactorSetup();
    }

    /**
     * من بدأ تفعيل التحقّق بخطوتين ولم يؤكّده يترك خلفه سرًّا معلّقًا في الحساب.
     * تركه يعني أن رموز الاسترداد موجودة دون أن يعرف المستخدم بها، فنمسحه.
     */
    private function discardAbandonedTwoFactorSetup(): void
    {
        $user = Auth::user();

        if ($user->two_factor_secret && is_null($user->two_factor_confirmed_at)) {
            $user->forceFill([
                'two_factor_secret' => null,
                'two_factor_recovery_codes' => null,
            ])->save();
        }
    }

    public function updatePassword(): void
    {
        $this->validate([
            'current_password' => ['required', 'string', 'current_password'],
            'password' => $this->passwordRules(),
        ], [
            'current_password.current_password' => 'كلمة المرور الحالية غير صحيحة.',
            'password.confirmed' => 'تأكيد كلمة المرور غير مطابق.',
        ], [
            'current_password' => 'كلمة المرور الحالية',
            'password' => 'كلمة المرور الجديدة',
        ]);

        Auth::user()->update(['password' => Hash::make($this->password)]);

        $this->reset(['current_password', 'password', 'password_confirmation']);

        $this->dispatch('notify', message: 'حُدّثت كلمة المرور.');
    }

    #[Computed]
    public function twoFactorEnabled(): bool
    {
        return ! is_null(Auth::user()->two_factor_confirmed_at ?? null);
    }

    /** الأقسام تظهر فقط عند تفعيل ميزتها في إعدادات Fortify. */
    #[Computed]
    public function twoFactorAvailable(): bool
    {
        return Features::enabled(Features::twoFactorAuthentication());
    }

    #[Computed]
    public function passkeysAvailable(): bool
    {
        return Features::enabled(Features::passkeys());
    }

    #[Computed]
    public function passkeys()
    {
        $user = Auth::user();

        return method_exists($user, 'passkeys') ? $user->passkeys()->latest()->get() : collect();
    }

    public function loadPasskeys(): void
    {
        unset($this->passkeys);
    }

    public function deletePasskey(int $id): void
    {
        Auth::user()->passkeys()->whereKey($id)->delete();

        unset($this->passkeys);

        $this->dispatch('notify', message: 'حُذف مفتاح المرور.');
    }
}; ?>

<div>
    @php
        // الوصف يذكر ما هو متاح فعلًا فقط — لا معنى لذكر ميزة معطّلة في الإعدادات
        $available = array_filter([
            'كلمة المرور',
            $this->twoFactorAvailable ? 'التحقّق بخطوتين' : null,
            $this->passkeysAvailable ? 'مفاتيح المرور' : null,
        ]);
    @endphp

    <x-admin.page-header title="الأمان" :description="implode('، ', $available) . '.'" />

    <div class="max-w-2xl space-y-6">

        {{-- ================= كلمة المرور ================= --}}
        <x-admin.card title="تغيير كلمة المرور">
            <form wire:submit="updatePassword" class="grid gap-5">
                <x-ui.field label="كلمة المرور الحالية" for="current_password" required :error="$errors->first('current_password')">
                    <x-ui.input id="current_password" wire:model="current_password" type="password" dir="ltr"
                        autocomplete="current-password" :invalid="$errors->has('current_password')" />
                </x-ui.field>

                <x-ui.field label="كلمة المرور الجديدة" for="password" required :error="$errors->first('password')">
                    <x-ui.input id="password" wire:model="password" type="password" dir="ltr"
                        autocomplete="new-password" :invalid="$errors->has('password')" />
                </x-ui.field>

                <x-ui.field label="تأكيد كلمة المرور" for="password_confirmation" required>
                    <x-ui.input id="password_confirmation" wire:model="password_confirmation" type="password" dir="ltr"
                        autocomplete="new-password" />
                </x-ui.field>

                <div>
                    <x-ui.button type="submit" icon="check">تحديث كلمة المرور</x-ui.button>
                </div>
            </form>
        </x-admin.card>

        {{-- ================= التحقّق بخطوتين ================= --}}
        @if ($this->twoFactorAvailable)
        <x-admin.card title="التحقّق بخطوتين" description="طبقة حماية إضافية عبر تطبيق مصادقة على جوالك.">
            <div class="flex flex-wrap items-center gap-4">
                <x-ui.badge :variant="$this->twoFactorEnabled ? 'success' : 'neutral'"
                    :icon="$this->twoFactorEnabled ? 'check-circle' : 'info'">
                    {{ $this->twoFactorEnabled ? 'مفعّل' : 'غير مفعّل' }}
                </x-ui.badge>

                @if ($this->twoFactorEnabled)
                    <form method="POST" action="{{ route('two-factor.disable') }}" class="ms-auto">
                        @csrf
                        @method('DELETE')
                        <x-ui.button type="submit" variant="ghost" class="text-red-600 dark:text-red-400">
                            تعطيل
                        </x-ui.button>
                    </form>
                @else
                    <form method="POST" action="{{ route('two-factor.enable') }}" class="ms-auto">
                        @csrf
                        <x-ui.button type="submit" variant="primary" icon="check">تفعيل</x-ui.button>
                    </form>
                @endif
            </div>

            @if ($this->twoFactorEnabled)
                <div class="pt-4 mt-4 border-t border-ink-200 dark:border-ink-800">
                    <p class="mb-3 text-xs text-ink-500 dark:text-ink-400">
                        احتفظ برموز الاسترداد في مكان آمن — تمكّنك من الدخول عند فقدان جوالك.
                    </p>

                    <div class="grid grid-cols-2 gap-2 p-4 font-mono text-xs bg-ink-50 rounded-xl dark:bg-ink-950/50" dir="ltr">
                        @foreach (json_decode(decrypt(auth()->user()->two_factor_recovery_codes ?? encrypt('[]')), true) ?? [] as $code)
                            <span class="text-ink-700 dark:text-ink-300">{{ $code }}</span>
                        @endforeach
                    </div>

                    <form method="POST" action="{{ route('two-factor.regenerate-recovery-codes') }}" class="mt-3">
                        @csrf
                        <x-ui.button type="submit" variant="ghost" size="sm" icon="refresh">
                            توليد رموز جديدة
                        </x-ui.button>
                    </form>
                </div>
            @endif
        </x-admin.card>
        @endif

        {{-- ================= مفاتيح المرور ================= --}}
        @if ($this->passkeysAvailable)
        <x-admin.card title="مفاتيح المرور" description="دخول ببصمة الجهاز أو التعرّف على الوجه بدل كلمة المرور.">
            <x-passkey-registration />

            @if ($this->passkeys->isEmpty())
                <p class="mt-4 text-sm text-ink-500 dark:text-ink-400">لا توجد مفاتيح مرور مسجّلة بعد.</p>
            @endif

            @if ($this->passkeys->isNotEmpty())
                <ul class="mt-5 divide-y divide-ink-200 dark:divide-ink-800">
                    @foreach ($this->passkeys as $passkey)
                        <li class="flex items-center gap-3 py-3">
                            <span class="text-ink-400"><x-icon name="user" :size="17" /></span>

                            <div class="min-w-0 grow">
                                <p class="text-sm font-bold text-ink-900 dark:text-ink-100">{{ $passkey->name }}</p>
                                <p class="text-xs text-ink-500 dark:text-ink-400">أُضيف {{ $passkey->created_at->diffForHumans() }}</p>
                            </div>

                            <button type="button" wire:click="deletePasskey({{ $passkey->id }})" wire:confirm="حذف مفتاح المرور هذا؟"
                                class="p-2 text-red-600 transition-colors rounded-lg hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-950/40"
                                aria-label="حذف">
                                <x-icon name="trash" :size="15" />
                            </button>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-admin.card>
        @endif
    </div>
</div>
