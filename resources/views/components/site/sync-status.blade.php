{{--
    مؤشّر حالة مزامنة المحتوى مع قاعدة بيانات المتصفح.
    يقرأ حالته من مخزن Alpine المسجّل في resources/js/sync.js
--}}
<button
    type="button"
    x-data
    x-on:click="$store.sync.refresh()"
    class="inline-flex items-center gap-1.5 rounded-lg px-2 py-1 text-xs text-ink-500 transition-colors hover:bg-ink-100 dark:hover:bg-ink-800"
    x-bind:title="$store.sync.label + ($store.sync.itemCount ? ' — ' + $store.sync.itemCount + ' عنصر محفوظ محليًا' : '')"
>
    <template x-if="$store.sync.state === 'syncing'">
        <span class="animate-spin"><x-icon name="refresh" :size="14" /></span>
    </template>
    <template x-if="$store.sync.state === 'synced'">
        <span class="text-emerald-600 dark:text-emerald-500"><x-icon name="cloud-check" :size="14" /></span>
    </template>
    <template x-if="$store.sync.state === 'offline'">
        <span class="text-amber-600 dark:text-amber-500"><x-icon name="cloud-off" :size="14" /></span>
    </template>
    <template x-if="$store.sync.state === 'error'">
        <span class="text-ink-400"><x-icon name="cloud-off" :size="14" /></span>
    </template>
    <template x-if="$store.sync.state === 'idle'">
        <span><x-icon name="database" :size="14" /></span>
    </template>

    <span x-text="$store.sync.label"></span>
</button>
