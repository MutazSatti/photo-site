<x-layouts::auth title="تعيين كلمة مرور جديدة">
    <h1 class="text-xl font-extrabold text-ink-900 dark:text-ink-50">تعيين كلمة مرور جديدة</h1>

    @if ($errors->any())
        <x-ui.alert variant="danger" class="mt-5">{{ $errors->first() }}</x-ui.alert>
    @endif

    <form method="POST" action="{{ route('password.update') }}" class="grid gap-5 mt-6">
        @csrf
        <input type="hidden" name="token" value="{{ request()->route('token') }}">

        <x-ui.field label="البريد الإلكتروني" for="email" required>
            <x-ui.input id="email" name="email" type="email" dir="ltr"
                value="{{ old('email', request()->string('email')) }}" required autocomplete="username"
                :invalid="$errors->has('email')" />
        </x-ui.field>

        <x-ui.field label="كلمة المرور الجديدة" for="password" required>
            <x-ui.input id="password" name="password" type="password" dir="ltr" required autofocus
                autocomplete="new-password" :invalid="$errors->has('password')" />
        </x-ui.field>

        <x-ui.field label="تأكيد كلمة المرور" for="password_confirmation" required>
            <x-ui.input id="password_confirmation" name="password_confirmation" type="password" dir="ltr" required
                autocomplete="new-password" />
        </x-ui.field>

        <x-ui.button type="submit" variant="primary" size="lg" icon="check" class="w-full">
            حفظ كلمة المرور
        </x-ui.button>
    </form>
</x-layouts::auth>
