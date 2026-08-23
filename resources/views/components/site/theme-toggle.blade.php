{{--
    تبديل الوضع الفاتح/الداكن.
    الاختيار يُحفظ في localStorage ويُطبَّق في <head> قبل الرسم لتفادي الومضة.
--}}
<button
    type="button"
    x-data="{
        dark: document.documentElement.classList.contains('dark'),
        toggle() {
            this.dark = !this.dark;
            document.documentElement.classList.toggle('dark', this.dark);
            try { localStorage.setItem('theme', this.dark ? 'dark' : 'light'); } catch (e) {}
        },
    }"
    x-on:click="toggle()"
    class="inline-flex items-center justify-center transition-colors rounded-lg size-10 text-ink-600 hover:bg-ink-100 dark:text-ink-400 dark:hover:bg-ink-800"
    x-bind:aria-label="dark ? 'التبديل إلى الوضع الفاتح' : 'التبديل إلى الوضع الداكن'"
    title="تبديل مظهر الموقع"
>
    <span x-show="!dark"><x-icon name="moon" :size="18" /></span>
    <span x-show="dark" x-cloak><x-icon name="sun" :size="18" /></span>
</button>
