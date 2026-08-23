<x-layouts::auth title="استعادة كلمة المرور">
    <h1 class="text-xl font-extrabold text-ink-900 dark:text-ink-50">استعادة كلمة المرور</h1>
    <p class="mt-1.5 text-sm leading-7 text-ink-500 dark:text-ink-400">
        أدخل بريدك الإلكتروني ويصلك رابط لتعيين كلمة مرور جديدة.
    </p>

    @if (session('status'))
        <x-ui.alert variant="success" class="mt-5">{{ session('status') }}</x-ui.alert>
    @endif

    @if ($errors->any())
        <x-ui.alert variant="danger" class="mt-5">{{ $errors->first() }}</x-ui.alert>
    @endif

    <form method="POST" action="{{ route('password.email') }}" class="grid gap-5 mt-6">
        @csrf

        <x-ui.field label="البريد الإلكتروني" for="email" required>
            <x-ui.input id="email" name="email" type="email" dir="ltr" value="{{ old('email') }}" required autofocus
                autocomplete="username" placeholder="you@example.com" :invalid="$errors->has('email')" />
        </x-ui.field>

        <x-ui.button type="submit" variant="primary" size="lg" icon="send" class="w-full">
            إرسال رابط الاستعادة
        </x-ui.button>
    </form>

    <p class="mt-6 text-sm text-center text-ink-500 dark:text-ink-400">
        <a href="{{ route('login') }}" wire:navigate class="font-bold text-brand-600 dark:text-brand-400">العودة لتسجيل الدخول</a>
    </p>
</x-layouts::auth>
