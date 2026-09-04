/**
 * مزامنة بيانات الموقع مع قاعدة بيانات المتصفح (IndexedDB).
 *
 * الفكرة: كل البيانات النصية والرقمية — الأقسام، الأعمال، المقالات، الإعدادات،
 * الأسئلة الشائعة، الآراء، الجهات — تُحفظ محليًا في المتصفح. الصور والفيديوهات مستثناة
 * تمامًا؛ يُخزَّن منها المسار والأبعاد فقط ويتكفّل كاش المتصفح بالملفات نفسها.
 *
 * الفائدة العملية:
 *   - بحث فوري داخل كل محتوى الموقع دون أي طلب شبكة
 *   - بقاء المحتوى النصي متاحًا عند انقطاع الاتصال
 *   - تقليل الطلبات: البصمة الخفيفة تُفحص أولًا، والحمولة الكاملة لا تُسحب
 *     إلا عند تغيّر المحتوى فعليًا
 */

const DB_NAME = 'mutaz-satti-site';
// 2: أُضيف مخزن الجهات — رفع الرقم يجعل onupgradeneeded ينشئه لدى زوّار قدامى
const DB_VERSION = 2;

const STORES = [
    'sections',
    'categories',
    'posts',
    'faqs',
    'testimonials',
    'clients',
];

const KEYVAL_STORES = ['settings', 'meta'];

class SiteSync {
    constructor() {
        this.db = null;
        this.status = {
            state: 'idle', // idle | syncing | synced | offline | error
            version: null,
            lastSync: null,
            counts: {},
        };
        this.ready = this.open();
    }

    // ---------------------------------------------------------------- قاعدة البيانات

    open() {
        return new Promise((resolve, reject) => {
            if (!('indexedDB' in window)) {
                this.setStatus({ state: 'error' });
                return reject(new Error('المتصفح لا يدعم IndexedDB'));
            }

            const request = indexedDB.open(DB_NAME, DB_VERSION);

            request.onupgradeneeded = (event) => {
                const db = event.target.result;

                STORES.forEach((name) => {
                    if (!db.objectStoreNames.contains(name)) {
                        const store = db.createObjectStore(name, { keyPath: 'id' });

                        if (name === 'posts') {
                            store.createIndex('slug', 'slug', { unique: true });
                            store.createIndex('section_slug', 'section_slug');
                            store.createIndex('category_slug', 'category_slug');
                        }

                        if (name === 'sections' || name === 'categories') {
                            store.createIndex('slug', 'slug');
                        }
                    }
                });

                KEYVAL_STORES.forEach((name) => {
                    if (!db.objectStoreNames.contains(name)) {
                        db.createObjectStore(name);
                    }
                });
            };

            request.onsuccess = (event) => {
                this.db = event.target.result;
                resolve(this.db);
            };

            request.onerror = () => {
                this.setStatus({ state: 'error' });
                reject(request.error);
            };
        });
    }

    tx(stores, mode = 'readonly') {
        return this.db.transaction(stores, mode);
    }

    /** يستبدل محتوى مخزن كامل بالبيانات الجديدة داخل معاملة واحدة. */
    replaceStore(name, rows) {
        return new Promise((resolve, reject) => {
            const tx = this.tx([name], 'readwrite');
            const store = tx.objectStore(name);

            store.clear();
            rows.forEach((row) => store.put(row));

            tx.oncomplete = () => resolve(rows.length);
            tx.onerror = () => reject(tx.error);
        });
    }

    putKeyVal(storeName, key, value) {
        return new Promise((resolve, reject) => {
            const tx = this.tx([storeName], 'readwrite');
            tx.objectStore(storeName).put(value, key);
            tx.oncomplete = () => resolve(value);
            tx.onerror = () => reject(tx.error);
        });
    }

    getKeyVal(storeName, key) {
        return new Promise((resolve, reject) => {
            const request = this.tx([storeName]).objectStore(storeName).get(key);
            request.onsuccess = () => resolve(request.result);
            request.onerror = () => reject(request.error);
        });
    }

    // ---------------------------------------------------------------- القراءة

    async all(storeName) {
        await this.ready;

        return new Promise((resolve, reject) => {
            const request = this.tx([storeName]).objectStore(storeName).getAll();
            request.onsuccess = () => resolve(request.result || []);
            request.onerror = () => reject(request.error);
        });
    }

    async find(storeName, id) {
        await this.ready;

        return new Promise((resolve, reject) => {
            const request = this.tx([storeName]).objectStore(storeName).get(id);
            request.onsuccess = () => resolve(request.result || null);
            request.onerror = () => reject(request.error);
        });
    }

    async findBy(storeName, indexName, value) {
        await this.ready;

        return new Promise((resolve, reject) => {
            const request = this.tx([storeName]).objectStore(storeName).index(indexName).getAll(value);
            request.onsuccess = () => resolve(request.result || []);
            request.onerror = () => reject(request.error);
        });
    }

    async settings() {
        await this.ready;

        return (await this.getKeyVal('settings', 'all')) || {};
    }

    async setting(key, fallback = null) {
        const all = await this.settings();

        return all[key] ?? fallback;
    }

    // ---------------------------------------------------------------- البحث

    /**
     * بحث فوري في العناوين والملخّصات والنصوص الكاملة والأسئلة الشائعة.
     * يعمل بالكامل من الذاكرة المحلية — بلا أي طلب شبكة.
     */
    async search(query, { limit = 20 } = {}) {
        const term = normalize(query);

        if (term.length < 2) {
            return [];
        }

        const [posts, faqs, categories] = await Promise.all([
            this.all('posts'),
            this.all('faqs'),
            this.all('categories'),
        ]);

        const results = [];

        posts.forEach((post) => {
            const score = scoreMatch(term, [
                { text: post.title, weight: 10 },
                { text: post.subtitle, weight: 6 },
                { text: post.excerpt, weight: 4 },
                { text: post.section_name, weight: 3 },
                { text: post.category_name, weight: 3 },
                { text: post.location, weight: 2 },
                { text: post.body_text, weight: 1 },
            ]);

            if (score > 0) {
                results.push({
                    type: 'post',
                    typeLabel: post.section_name || 'عمل',
                    title: post.title,
                    description: post.excerpt || truncate(post.body_text, 120),
                    url: post.url,
                    thumb: post.cover ? post.cover.thumb : null,
                    score,
                });
            }
        });

        categories.forEach((category) => {
            const score = scoreMatch(term, [
                { text: category.name, weight: 9 },
                { text: category.tagline, weight: 4 },
                { text: category.description, weight: 2 },
            ]);

            if (score > 0) {
                results.push({
                    type: 'category',
                    typeLabel: 'قسم',
                    title: category.name,
                    description: category.tagline,
                    url: category.url,
                    thumb: null,
                    score,
                });
            }
        });

        faqs.forEach((faq) => {
            const score = scoreMatch(term, [
                { text: faq.question, weight: 8 },
                { text: faq.answer, weight: 2 },
            ]);

            if (score > 0) {
                results.push({
                    type: 'faq',
                    typeLabel: 'سؤال شائع',
                    title: faq.question,
                    description: truncate(faq.answer, 120),
                    url: '/faq#q' + faq.id,
                    thumb: null,
                    score,
                });
            }
        });

        return results.sort((a, b) => b.score - a.score).slice(0, limit);
    }

    // ---------------------------------------------------------------- المزامنة

    /**
     * يفحص البصمة أولًا؛ إن لم تتغيّر عن المخزّنة محليًا لا يُسحب شيء.
     * @param {boolean} force تجاهل البصمة واسحب الحمولة الكاملة
     */
    async sync({ force = false } = {}) {
        await this.ready;

        if (!navigator.onLine) {
            this.setStatus({ state: 'offline' });

            return { changed: false, reason: 'offline' };
        }

        const local = (await this.getKeyVal('meta', 'manifest')) || null;

        try {
            this.setStatus({ state: 'syncing' });

            if (!force) {
                const remote = await fetchJson('/sync/manifest');

                if (local && remote.version === local.version) {
                    this.setStatus({
                        state: 'synced',
                        version: local.version,
                        lastSync: local.syncedAt || null,
                        counts: local.counts || {},
                    });

                    return { changed: false, reason: 'up-to-date' };
                }
            }

            const payload = await fetchJson('/sync/data');

            await Promise.all([
                this.replaceStore('sections', payload.sections),
                this.replaceStore('categories', payload.categories),
                this.replaceStore('posts', payload.posts),
                this.replaceStore('faqs', payload.faqs),
                this.replaceStore('testimonials', payload.testimonials),
                this.replaceStore('clients', payload.clients),
                this.putKeyVal('settings', 'all', payload.settings),
            ]);

            const syncedAt = new Date().toISOString();

            await this.putKeyVal('meta', 'manifest', {
                ...payload.manifest,
                syncedAt,
            });

            this.setStatus({
                state: 'synced',
                version: payload.manifest.version,
                lastSync: syncedAt,
                counts: payload.manifest.counts,
            });

            window.dispatchEvent(new CustomEvent('sync:updated', { detail: payload.manifest }));

            return { changed: true, version: payload.manifest.version };
        } catch (error) {
            // الفشل هنا لا يعطّل الموقع — الصفحات تُصيَّر من الخادم أصلًا
            this.setStatus({ state: navigator.onLine ? 'error' : 'offline' });

            return { changed: false, reason: 'error', error };
        }
    }

    setStatus(patch) {
        this.status = { ...this.status, ...patch };

        window.dispatchEvent(new CustomEvent('sync:status', { detail: this.status }));
    }

    /** يمسح كل ما هو مخزّن محليًا — للتشخيص أو عند تغيير بنية البيانات. */
    async clear() {
        await this.ready;

        await Promise.all(
            [...STORES, ...KEYVAL_STORES].map(
                (name) =>
                    new Promise((resolve) => {
                        const tx = this.tx([name], 'readwrite');
                        tx.objectStore(name).clear();
                        tx.oncomplete = resolve;
                    }),
            ),
        );

        this.setStatus({ state: 'idle', version: null, lastSync: null, counts: {} });
    }
}

// -------------------------------------------------------------------- أدوات مساعدة

async function fetchJson(url) {
    const response = await fetch(url, {
        headers: { Accept: 'application/json' },
        credentials: 'same-origin',
    });

    if (!response.ok) {
        throw new Error('فشل الطلب: ' + response.status);
    }

    return response.json();
}

/**
 * توحيد النص العربي قبل المقارنة: إزالة التشكيل، وتوحيد صور الألف والياء
 * والتاء المربوطة. بدون هذا لا يطابق البحث عن "مؤتمرات" كلمة "مؤتمرات" المشكّلة.
 */
function normalize(text) {
    if (!text) return '';

    return String(text)
        .toLowerCase()
        .replace(/[ً-ْٰـ]/g, '')
        .replace(/[أإآٱ]/g, 'ا')
        .replace(/ى/g, 'ي')
        .replace(/ة/g, 'ه')
        .replace(/[ؤئ]/g, 'ء')
        .replace(/\s+/g, ' ')
        .trim();
}

function scoreMatch(term, fields) {
    let score = 0;

    fields.forEach(({ text, weight }) => {
        const haystack = normalize(text);

        if (!haystack) return;

        if (haystack === term) {
            score += weight * 3;
        } else if (haystack.startsWith(term)) {
            score += weight * 2;
        } else if (haystack.includes(term)) {
            score += weight;
        }
    });

    return score;
}

function truncate(text, length) {
    if (!text) return '';

    return text.length > length ? text.slice(0, length).trim() + '…' : text;
}

// -------------------------------------------------------------------- التهيئة

const siteSync = new SiteSync();

window.SiteSync = siteSync;

// مزامنة أولى بعد استقرار الصفحة حتى لا تزاحم تحميل المحتوى المرئي
const startSync = () => {
    if ('requestIdleCallback' in window) {
        requestIdleCallback(() => siteSync.sync(), { timeout: 3000 });
    } else {
        setTimeout(() => siteSync.sync(), 1200);
    }
};

if (document.readyState === 'complete') {
    startSync();
} else {
    window.addEventListener('load', startSync, { once: true });
}

// إعادة المحاولة عند عودة الاتصال، وفحص خفيف عند العودة إلى التبويب
window.addEventListener('online', () => siteSync.sync());
window.addEventListener('offline', () => siteSync.setStatus({ state: 'offline' }));

document.addEventListener('visibilitychange', () => {
    if (document.visibilityState === 'visible' && siteSync.status.state !== 'syncing') {
        siteSync.sync();
    }
});

// تسجيل مخزن Alpine ليصل إليه أي مكوّن عبر $store.sync
document.addEventListener('alpine:init', () => {
    window.Alpine.store('sync', {
        state: siteSync.status.state,
        version: siteSync.status.version,
        lastSync: siteSync.status.lastSync,
        counts: siteSync.status.counts,

        init() {
            window.addEventListener('sync:status', (event) => {
                Object.assign(this, event.detail);
            });
        },

        get label() {
            return {
                idle: 'بانتظار المزامنة',
                syncing: 'جارٍ المزامنة',
                synced: 'المحتوى محدّث',
                offline: 'دون اتصال — يُعرض المحتوى المحفوظ',
                error: 'تعذّرت المزامنة',
            }[this.state] || '';
        },

        get itemCount() {
            return this.counts.posts || 0;
        },

        refresh() {
            return siteSync.sync({ force: true });
        },
    });

    // $sync متاح داخل أي x-data للوصول المباشر للبيانات المحفوظة
    window.Alpine.magic('sync', () => siteSync);
});

export default siteSync;
