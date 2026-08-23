<x-layouts::auth title="تسجيل الدخول">
    <h1 class="text-xl font-extrabold text-ink-900 dark:text-ink-50">تسجيل الدخول</h1>
    <p class="mt-1.5 text-sm text-ink-500 dark:text-ink-400">ادخل إلى لوحة تحكم الموقع.</p>

    @if (session('status'))
        <x-ui.alert variant="success" class="mt-5">{{ session('status') }}</x-ui.alert>
    @endif

    @if ($errors->any())
        <x-ui.alert variant="danger" class="mt-5">{{ $errors->first() }}</x-ui.alert>
    @endif

    <x-passkey-verify />

    <form method="POST" action="{{ route('login.store') }}" class="grid gap-5 mt-6">
        @csrf

        <x-ui.field label="البريد الإلكتروني" for="email" required>
            <x-ui.input
                id="email"
                name="email"
                type="email"
                dir="ltr"
                value="{{ old('email') }}"
                required
                autofocus
                autocomplete="username"
                placeholder="you@example.com"
                :invalid="$errors->has('email')"
            />
        </x-ui.field>

        <x-ui.field label="كلمة المرور" for="password" required>
            <div x-data="{ show: false }" class="relative">
                <x-ui.input
                    id="password"
                    name="password"
                    x-bind:type="show ? 'text' : 'password'"
                    type="password"
                    dir="ltr"
                    required
                    autocomplete="current-password"
                    class="pe-11"
                    :invalid="$errors->has('password')"
                />

                <button
                    type="button"
                    x-on:click="show = !show"
                    class="absolute -translate-y-1/2 end-3 top-1/2 text-ink-400 transition-colors hover:text-ink-600 dark:hover:text-ink-200"
                    x-bind:aria-label="show ? 'إخفاء كلمة المرور' : 'إظهار كلمة المرور'"
                >
                    <x-icon name="eye" :size="17" />
                </button>
            </div>
        </x-ui.field>

        <div class="flex items-center justify-between gap-4">
            <label class="inline-flex items-center gap-2 text-sm cursor-pointer text-ink-700 dark:text-ink-300">
                <input
                    type="checkbox"
                    name="remember"
                    @checked(old('remember'))
                    class="border rounded size-4 border-ink-300 text-brand-500 focus:ring-brand-500/40 dark:border-ink-600 dark:bg-ink-800"
                >
                تذكّرني
            </label>

            @if (Route::has('password.request'))
                <a href="{{ route('password.request') }}" wire:navigate class="text-sm font-bold transition-colors text-brand-600 hover:text-brand-500 dark:text-brand-400">
                    نسيت كلمة المرور؟
                </a>
            @endif
        </div>

        <x-ui.button type="submit" variant="primary" size="lg" icon="logout" class="w-full">
            دخول
        </x-ui.button>
    </form>
</x-layouts::auth>
