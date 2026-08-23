@assets
    @vite('resources/js/passkeys.js')
@endassets

{{-- تسجيل مفتاح مرور جديد عبر WebAuthn --}}
<div
    x-data="{
        supported: false,
        showForm: false,
        name: '',
        loading: false,
        error: null,
        updateSupport() {
            this.supported = Boolean(window.Passkeys?.isSupported());
        },
        defaultName() {
            const ua = navigator.userAgent;

            const browser = [
                { pattern: /Edg|Edge/, name: 'Edge' },
                { pattern: /OPR|Opera|OPiOS/, name: 'Opera' },
                { pattern: /Firefox|FxiOS/, name: 'Firefox' },
                { pattern: /Chrome|CriOS/, name: 'Chrome' },
                { pattern: /Safari/, name: 'Safari' },
            ].find(({ pattern }) => pattern.test(ua))?.name;

            const os = [
                { pattern: /iPhone/, name: 'iPhone' },
                { pattern: /iPad|Macintosh(?=.*Mobile)/, name: 'iPad' },
                { pattern: /Android/, name: 'Android' },
                { pattern: /Mac/, name: 'Mac' },
                { pattern: /Windows/, name: 'Windows' },
            ].find(({ pattern }) => pattern.test(ua))?.name;

            return [browser, os].filter(Boolean).join(' على ') || 'جهازي';
        },
        init() {
            this.name = this.defaultName();
            this.updateSupport();
            window.addEventListener('passkeys:ready', () => this.updateSupport(), { once: true });
        },
        async register() {
            if (!this.name.trim()) return;

            this.loading = true;
            this.error = null;

            try {
                await window.Passkeys.register({ name: this.name });
                this.name = this.defaultName();
                this.showForm = false;
                await $wire.loadPasskeys();
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
    <template x-if="!supported">
        <p class="text-sm text-ink-500 dark:text-ink-400">هذا المتصفح لا يدعم مفاتيح المرور.</p>
    </template>

    <template x-if="supported && !showForm">
        <div>
            <x-ui.button x-on:click="showForm = true" variant="primary" icon="plus">
                إضافة مفتاح مرور
            </x-ui.button>
        </div>
    </template>

    <template x-if="supported && showForm">
        <div class="p-4 space-y-4 bg-ink-50 rounded-xl dark:bg-ink-950/50">
            <x-ui.field label="اسم المفتاح" hint="اسم يساعدك على تمييز الجهاز لاحقًا.">
                <x-ui.input
                    x-model="name"
                    placeholder="مثال: آيفوني"
                    x-on:keydown.enter.prevent="register()"
                    x-ref="passkeyName"
                    x-init="$nextTick(() => $refs.passkeyName?.focus())"
                />
            </x-ui.field>

            <p x-show="error" x-text="error" x-cloak class="text-sm font-bold text-red-600 dark:text-red-400"></p>

            <div class="flex gap-2">
                <x-ui.button x-on:click="register()" x-bind:disabled="loading || !name.trim()" variant="primary" icon="check">
                    <span x-show="!loading">تسجيل المفتاح</span>
                    <span x-show="loading" x-cloak>جارٍ التسجيل…</span>
                </x-ui.button>

                <x-ui.button x-on:click="showForm = false; error = null" variant="ghost">إلغاء</x-ui.button>
            </div>
        </div>
    </template>
</div>
