<x-layouts::auth title="تأكيد البريد الإلكتروني">
    <h1 class="text-xl font-extrabold text-ink-900 dark:text-ink-50">تأكيد البريد الإلكتروني</h1>
    <p class="mt-1.5 text-sm leading-7 text-ink-500 dark:text-ink-400">
        أرسلنا رابط تأكيد إلى بريدك. افتحه لتفعيل الحساب، وإن لم يصلك اطلب إرساله من جديد.
    </p>

    @if (session('status') === 'verification-link-sent')
        <x-ui.alert variant="success" class="mt-5">
            أُرسل رابط تأكيد جديد إلى بريدك الإلكتروني.
        </x-ui.alert>
    @endif

    <div class="grid gap-3 mt-6">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <x-ui.button type="submit" variant="primary" size="lg" icon="send" class="w-full">
                إعادة إرسال رابط التأكيد
            </x-ui.button>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <x-ui.button type="submit" variant="ghost" class="w-full" icon="logout">
                تسجيل الخروج
            </x-ui.button>
        </form>
    </div>
</x-layouts::auth>
