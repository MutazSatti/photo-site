<x-layouts::auth title="تأكيد كلمة المرور">
    <h1 class="text-xl font-extrabold text-ink-900 dark:text-ink-50">تأكيد كلمة المرور</h1>
    <p class="mt-1.5 text-sm leading-7 text-ink-500 dark:text-ink-400">
        هذه منطقة حسّاسة — أكّد كلمة المرور للمتابعة.
    </p>

    @if ($errors->any())
        <x-ui.alert variant="danger" class="mt-5">{{ $errors->first() }}</x-ui.alert>
    @endif

    <form method="POST" action="{{ route('password.confirm.store') }}" class="grid gap-5 mt-6">
        @csrf

        <x-ui.field label="كلمة المرور" for="password" required>
            <x-ui.input id="password" name="password" type="password" dir="ltr" required autofocus
                autocomplete="current-password" :invalid="$errors->has('password')" />
        </x-ui.field>

        <x-ui.button type="submit" variant="primary" size="lg" icon="check" class="w-full">
            تأكيد
        </x-ui.button>
    </form>
</x-layouts::auth>
