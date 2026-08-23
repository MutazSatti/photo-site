{{--
    بحث فوري يعمل بالكامل من قاعدة بيانات المتصفح (IndexedDB) — بلا أي طلب شبكة،
    فالنتائج تظهر أثناء الكتابة وتبقى متاحة حتى دون اتصال.
--}}
<div
    x-data="{
        open: false,
        query: '',
        results: [],
        active: 0,
        searching: false,
        async run() {
            const q = this.query.trim();

            if (q.length < 2) {
                this.results = [];
                return;
            }

            this.searching = true;
            this.results = await $sync.search(q, { limit: 12 });
            this.active = 0;
            this.searching = false;
        },
        show() {
            this.open = true;
            this.$nextTick(() => this.$refs.input?.focus());
        },
        close() {
            this.open = false;
            this.query = '';
            this.results = [];
        },
        move(step) {
            if (!this.results.length) return;
            this.active = (this.active + step + this.results.length) % this.results.length;
        },
        go() {
            const item = this.results[this.active];
            if (item) {
                this.close();
                window.Livewire.navigate(item.url);
            }
        },
    }"
    x-on:keydown.window.prevent.cmd.k="show()"
    x-on:keydown.window.prevent.ctrl.k="show()"
    x-on:livewire:navigated.window="close()"
>
    <button
        type="button"
        x-on:click="show()"
        class="inline-flex items-center justify-center transition-colors rounded-lg size-10 text-ink-600 hover:bg-ink-100 dark:text-ink-400 dark:hover:bg-ink-800"
        aria-label="البحث في الموقع"
        title="البحث (Ctrl + K)"
    >
        <x-icon name="search" :size="18" />
    </button>

    {{-- طبقة البحث --}}
    <div
        x-show="open"
        x-cloak
        x-transition:enter="transition ease-smooth duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        class="fixed inset-0 z-100 px-4 pt-20 bg-ink-950/60 backdrop-blur-sm"
        x-on:click.self="close()"
        x-on:keydown.escape="close()"
        x-on:keydown.down.prevent="move(1)"
        x-on:keydown.up.prevent="move(-1)"
        x-on:keydown.enter.prevent="go()"
        role="dialog"
        aria-modal="true"
        aria-label="البحث في الموقع"
    >
        <div class="max-w-2xl mx-auto overflow-hidden bg-white shadow-2xl rounded-2xl dark:bg-ink-900">
            <div class="relative border-b border-ink-200 dark:border-ink-800">
                <span class="absolute -translate-y-1/2 pointer-events-none start-5 top-1/2 text-ink-400">
                    <x-icon name="search" :size="19" />
                </span>

                <input
                    x-ref="input"
                    x-model="query"
                    x-on:input.debounce.150ms="run()"
                    type="search"
                    placeholder="ابحث في الأعمال والمقالات والأسئلة…"
                    class="w-full py-4 text-base bg-transparent border-0 ps-14 pe-14 text-ink-900 placeholder:text-ink-400 focus:outline-none dark:text-ink-100"
                    autocomplete="off"
                >

                <button
                    type="button"
                    x-on:click="close()"
                    class="absolute -translate-y-1/2 end-4 top-1/2 text-ink-400 transition-colors hover:text-ink-600 dark:hover:text-ink-200"
                    aria-label="إغلاق البحث"
                >
                    <x-icon name="close" :size="18" />
                </button>
            </div>

            {{-- النتائج --}}
            <div class="overflow-y-auto max-h-[60vh]">
                <template x-if="query.trim().length >= 2 && results.length === 0 && !searching">
                    <p class="px-6 py-10 text-sm text-center text-ink-500 dark:text-ink-400">
                        لا توجد نتائج مطابقة.
                    </p>
                </template>

                <template x-if="query.trim().length < 2">
                    <div class="px-6 py-8 text-center">
                        <p class="text-sm text-ink-500 dark:text-ink-400">اكتب حرفين على الأقل للبحث.</p>
                        <p class="mt-2 text-xs text-ink-400">
                            البحث يعمل من نسخة محفوظة في متصفحك — فوري وبلا اتصال.
                        </p>
                    </div>
                </template>

                <ul>
                    <template x-for="(item, index) in results" :key="item.type + item.url + index">
                        <li>
                            <a
                                x-bind:href="item.url"
                                wire:navigate
                                x-on:click="close()"
                                x-on:mouseenter="active = index"
                                class="flex items-start gap-4 px-5 py-3 transition-colors"
                                x-bind:class="active === index ? 'bg-ink-100 dark:bg-ink-800' : ''"
                            >
                                <template x-if="item.thumb">
                                    <img x-bind:src="item.thumb" x-bind:alt="item.title" loading="lazy"
                                        class="object-cover size-12 shrink-0 rounded-lg bg-ink-100 dark:bg-ink-800">
                                </template>

                                <template x-if="!item.thumb">
                                    <span class="flex items-center justify-center size-12 shrink-0 rounded-lg bg-ink-100 text-ink-400 dark:bg-ink-800">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                            <path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7z" />
                                            <path d="M14 2v4a2 2 0 0 0 2 2h4" />
                                        </svg>
                                    </span>
                                </template>

                                <span class="min-w-0 grow">
                                    <span class="flex items-center gap-2">
                                        <span class="text-sm font-bold truncate text-ink-900 dark:text-ink-100" x-text="item.title"></span>
                                        <span class="shrink-0 rounded-full bg-brand-50 px-2 py-0.5 text-[11px] font-bold text-brand-700 dark:bg-brand-950 dark:text-brand-400" x-text="item.typeLabel"></span>
                                    </span>
                                    <span class="block mt-0.5 line-clamp-1 text-xs text-ink-500 dark:text-ink-400" x-text="item.description"></span>
                                </span>
                            </a>
                        </li>
                    </template>
                </ul>
            </div>

            <div class="flex items-center gap-4 px-5 py-3 border-t bg-ink-50 border-ink-200 dark:border-ink-800 dark:bg-ink-950/50">
                <span class="flex items-center gap-1.5 text-[11px] text-ink-500 dark:text-ink-400">
                    <kbd class="rounded border border-ink-300 bg-white px-1.5 py-0.5 font-sans text-[10px] dark:border-ink-700 dark:bg-ink-800">↑</kbd>
                    <kbd class="rounded border border-ink-300 bg-white px-1.5 py-0.5 font-sans text-[10px] dark:border-ink-700 dark:bg-ink-800">↓</kbd>
                    للتنقّل
                </span>
                <span class="flex items-center gap-1.5 text-[11px] text-ink-500 dark:text-ink-400">
                    <kbd class="rounded border border-ink-300 bg-white px-1.5 py-0.5 font-sans text-[10px] dark:border-ink-700 dark:bg-ink-800">Enter</kbd>
                    للفتح
                </span>
                <span class="text-[11px] ms-auto text-ink-400" x-text="results.length ? results.length + ' نتيجة' : ''"></span>
            </div>
        </div>
    </div>
</div>
