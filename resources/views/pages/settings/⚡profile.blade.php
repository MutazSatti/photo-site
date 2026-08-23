<?php

use App\Concerns\ProfileValidationRules;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts::admin', ['title' => 'الملف الشخصي'])] class extends Component
{
    use ProfileValidationRules;

    public string $name = '';

    public string $email = '';

    public function mount(): void
    {
        $this->name = Auth::user()->name;
        $this->email = Auth::user()->email;
    }

    public function updateProfileInformation(): void
    {
        $user = Auth::user();

        $validated = $this->validate($this->profileRules($user->id));

        $user->fill($validated);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        $this->dispatch('notify', message: 'حُدّثت بيانات الحساب.');
    }

    public function resendVerificationNotification(): void
    {
        $user = Auth::user();

        if ($user->hasVerifiedEmail()) {
            $this->redirectIntended(default: route('admin.dashboard', absolute: false));

            return;
        }

        $user->sendEmailVerificationNotification();

        Session::flash('status', 'verification-link-sent');
    }

    #[Computed]
    public function hasUnverifiedEmail(): bool
    {
        return Auth::user() instanceof MustVerifyEmail && ! Auth::user()->hasVerifiedEmail();
    }
}; ?>

<div>
    <x-admin.page-header title="الملف الشخصي" description="بيانات حساب الدخول إلى لوحة التحكم." />

    <div class="max-w-2xl space-y-6">
        <x-admin.card title="البيانات الأساسية">
            <form wire:submit="updateProfileInformation" class="grid gap-5">
                <x-ui.field label="الاسم" for="name" required :error="$errors->first('name')">
                    <x-ui.input id="name" wire:model="name" autocomplete="name" :invalid="$errors->has('name')" />
                </x-ui.field>

                <x-ui.field label="البريد الإلكتروني" for="email" required :error="$errors->first('email')">
                    <x-ui.input id="email" wire:model="email" type="email" dir="ltr" autocomplete="email"
                        :invalid="$errors->has('email')" />
                </x-ui.field>

                @if ($this->hasUnverifiedEmail)
                    <x-ui.alert variant="warning" title="البريد غير مُفعّل">
                        <button type="button" wire:click="resendVerificationNotification" class="font-bold underline underline-offset-4">
                            أرسل رابط التفعيل من جديد
                        </button>

                        @if (session('status') === 'verification-link-sent')
                            <p class="mt-2 font-bold">أُرسل رابط جديد إلى بريدك.</p>
                        @endif
                    </x-ui.alert>
                @endif

                <div>
                    <x-ui.button type="submit" icon="check">حفظ</x-ui.button>
                </div>
            </form>
        </x-admin.card>

        <x-admin.card title="الأمان" description="كلمة المرور والتحقّق بخطوتين ومفاتيح المرور.">
            <x-ui.button href="{{ route('security.edit') }}" variant="outline" icon="settings">
                إعدادات الأمان
            </x-ui.button>
        </x-admin.card>
    </div>
</div>
