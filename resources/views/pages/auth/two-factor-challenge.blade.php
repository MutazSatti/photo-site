<x-layouts::auth title="التحقّق بخطوتين">
    <div x-data="{ recovery: false }">
        <h1 class="text-xl font-extrabold text-ink-900 dark:text-ink-50">التحقّق بخطوتين</h1>

        <p class="mt-1.5 text-sm leading-7 text-ink-500 dark:text-ink-400" x-show="!recovery">
            أدخل الرمز المعروض في تطبيق المصادقة لديك.
        </p>
        <p class="mt-1.5 text-sm leading-7 text-ink-500 dark:text-ink-400" x-show="recovery" x-cloak>
            أدخل أحد رموز الاسترداد التي حفظتها عند تفعيل التحقّق بخطوتين.
        </p>

        @if ($errors->any())
            <x-ui.alert variant="danger" class="mt-5">{{ $errors->first() }}</x-ui.alert>
        @endif

        <form method="POST" action="{{ route('two-factor.login.store') }}" class="grid gap-5 mt-6">
            @csrf

            <div x-show="!recovery">
                <x-ui.field label="رمز التحقّق" for="code">
                    <x-ui.input id="code" name="code" type="text" inputmode="numeric" dir="ltr"
                        autocomplete="one-time-code" placeholder="000000" class="tracking-[0.5em] text-center" />
                </x-ui.field>
            </div>

            <div x-show="recovery" x-cloak>
                <x-ui.field label="رمز الاسترداد" for="recovery_code">
                    <x-ui.input id="recovery_code" name="recovery_code" type="text" dir="ltr" autocomplete="one-time-code" />
                </x-ui.field>
            </div>

            <x-ui.button type="submit" variant="primary" size="lg" icon="check" class="w-full">
                تحقّق ودخول
            </x-ui.button>
        </form>

        <button
            type="button"
            x-on:click="recovery = !recovery"
            class="w-full mt-5 text-sm font-bold text-center transition-colors text-brand-600 hover:text-brand-500 dark:text-brand-400"
        >
            <span x-show="!recovery">استخدام رمز استرداد بدلًا من ذلك</span>
            <span x-show="recovery" x-cloak>العودة إلى رمز التطبيق</span>
        </button>
    </div>
</x-layouts::auth>
