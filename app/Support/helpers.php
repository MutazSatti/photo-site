<?php

use App\Models\Media;
use App\Models\Setting;
use App\Support\Seo;

if (! function_exists('seo')) {
    /**
     * حامل بيانات السيو للصفحة الحالية.
     * يُستدعى من mount() في صفحات Livewire ومن قالب <head>.
     */
    function seo(): Seo
    {
        return app(Seo::class);
    }
}

if (! function_exists('setting')) {
    /**
     * قيمة إعداد من لوحة التحكم، مع قيمة افتراضية عند غيابها.
     */
    function setting(string $key, mixed $default = null): mixed
    {
        return Setting::get($key, $default);
    }
}

if (! function_exists('whatsapp_url')) {
    /**
     * رابط محادثة واتساب مع رسالة جاهزة — يقلّل احتكاك التواصل كثيرًا.
     */
    function whatsapp_url(?string $message = null): string
    {
        $number = setting('contact_whatsapp', config('site.whatsapp'));

        $message ??= 'السلام عليكم، وصلت من موقعك وأرغب في الاستفسار عن خدمات التصوير.';

        return 'https://wa.me/'.$number.'?text='.rawurlencode($message);
    }
}

if (! function_exists('accreditations')) {
    /**
     * الاعتمادات الرسمية بأوصافها جاهزةً للعرض.
     *
     * ‎:owner‎ يُستبدل هنا لا في الملف، فيبقى اسم المالك في مصدر واحد
     * (config/site.php أو متغيّر البيئة) ولا يُكتب ثلاث مرات في الأوصاف.
     *
     * @return array<int, array<string, string>>
     */
    function accreditations(): array
    {
        $owner = config('site.owner_name');

        /** @var array<int, array<string, string>> $items */
        $items = config('site.accreditations', []);

        return array_map(function (array $a) use ($owner): array {
            if (isset($a['description'])) {
                $a['description'] = str_replace(':owner', (string) $owner, $a['description']);
            }

            return $a;
        }, $items);
    }
}

if (! function_exists('header_photo')) {
    /**
     * صورة ترويسة الصفحة إن وُجدت لهذا المفتاح، وإلا null.
     *
     * مصدران بترتيب أولوية:
     *
     * ١. ما رفعه المالك من اللوحة — سجلّ Media بـ usage = header:{المفتاح}.
     * ٢. الملف المشحون مع الشيفرة في public/images/headers/{المفتاح}.webp.
     *
     * الملف هو الافتراضي لا القاعدة: يرحل مع المستودع فيصل مع أول نشر بلا
     * هجرة ولا بذرة، ويبقى شبكةَ أمان يعود إليها الموقع إن حذف المالك رفعه.
     * والرفع يعلو عليه لأنه قرار صاحب الموقع لا قرارنا.
     *
     * الاستدعاء يتكرر في الصفحة الواحدة (مرة للترويسة ومرة لاختيار شكل
     * الأزرار)، والرفع يُقرأ من ذاكرة Media::headers لا من ذاكرة هنا: تلك
     * يُبطلها حذفُ الصورة من اللوحة، وذاكرةٌ محلية هنا كانت ستبقى بعده
     * فتعرض صورةً محذوفة. والمحفوظ هنا وجودُ الملف وحده — وهو لا يتغيّر
     * أثناء تنفيذ الطلب.
     */
    function header_photo(?string $key): ?string
    {
        if ($key === null || preg_match('/^[a-z0-9-]+$/', $key) !== 1) {
            return null;
        }

        $uploaded = Media::headers()[$key] ?? null;

        return $uploaded?->url('full') ?? header_photo_default($key);
    }
}

if (! function_exists('header_photo_default')) {
    /**
     * الصورة المشحونة مع الشيفرة لهذا المفتاح، بصرف النظر عمّا رُفع فوقها.
     *
     * تحتاجها اللوحة وحدها: لتقول للمالك إن لهذه الصفحة صورةً أصلية يعود
     * إليها الموقع لو حذف رفعه — فيحذف وهو مطمئنّ أن الصفحة لن تفرغ.
     */
    function header_photo_default(?string $key): ?string
    {
        /** @var array<string, bool> $exists */
        static $exists = [];

        // المفتاح يأتي من slug في قاعدة البيانات، والحصر هنا يمنع أن يتحوّل
        // أي مفتاح غريب إلى مسار يخرج من المجلد
        if ($key === null || preg_match('/^[a-z0-9-]+$/', $key) !== 1) {
            return null;
        }

        $file = 'images/headers/'.$key.'.webp';

        $exists[$key] ??= file_exists(public_path($file));

        return $exists[$key] ? asset($file) : null;
    }
}
