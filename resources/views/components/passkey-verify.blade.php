@props([
    'optionsRoute' => 'passkey.login-options',
    'submitRoute' => 'passkey.login',
])

@assets
    @vite('resources/js/passkeys.js')
@endassets

{{-- الدخول ببصمة الجهاز عبر WebAuthn — يظهر فقط إن دعمه المتصفح --}}
<div
    x-data="{
        supported: false,
        loading: false,
        error: null,
        updateSupport() {
            this.supported = Boolean(window.Passkeys?.isSupported());
        },
        init() {
            this.updateSupport();
            window.addEventListener('passkeys:ready', () => this.updateSupport(), { once: true });
        },
        async verify() {
            this.loading = true;
            this.error = null;
            try {
                const response = await window.Passkeys.verify({
                    routes: {
                        options: '{{ route($optionsRoute) }}',
                        submit: '{{ route($submitRoute) }}',
                    },
                });
                window.location.href = response.redirect || '{{ route('admin.dashboard') }}';
            } catch (e) {
                if (e.constructor?.name !== 'UserCancelledError') {
                    this.error = e.message;
                }
            } finally {
                this.loading = false;
            }
        },
    }"
>
    <template x-if="supported">
        <div class="mt-6">
            <x-ui.button
                variant="outline"
                class="w-full"
                icon="user"
                x-on:click="verify()"
                x-bind:disabled="loading"
            >
                <span x-show="!loading">الدخول بمفتاح المرور</span>
                <span x-show="loading" x-cloak>جارٍ التحقّق…</span>
            </x-ui.button>

            <p x-show="error" x-text="error" x-cloak class="mt-2 text-sm text-center text-red-600 dark:text-red-400"></p>

            <div class="relative mt-6">
                <div class="absolute inset-0 flex items-center">
                    <div class="w-full border-t border-ink-200 dark:border-ink-700"></div>
                </div>
                <div class="relative flex justify-center">
                    <span class="px-3 text-xs bg-white text-ink-500 dark:bg-ink-900 dark:text-ink-400">
                        أو بالبريد وكلمة المرور
                    </span>
                </div>
            </div>
        </div>
    </template>
</div>
