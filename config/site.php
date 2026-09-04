<?php

/*
|--------------------------------------------------------------------------
| بيانات الموقع الثابتة
|--------------------------------------------------------------------------
|
| القيم هنا هي المرجع الأساسي لبيانات Schema.org المهيكلة وصفحة التواصل.
| ما يمكن تعديله من لوحة التحكم يُقرأ عبر Setting::get() ويرجع لهذه القيم
| كقيمة افتراضية عند غيابه.
|
*/

return [

    'owner_name' => env('SITE_OWNER_NAME', 'معتز ساتي'),
    'owner_name_en' => env('SITE_OWNER_NAME_EN', 'Mutaz Satti'),
    'job_title' => 'مصور فوتوغرافي محترف',
    'job_title_en' => 'Professional Photographer',

    'phone' => env('SITE_PHONE', '+966556202624'),
    'phone_local' => env('SITE_PHONE_LOCAL', '0556202624'),
    'whatsapp' => env('SITE_WHATSAPP', '966556202624'),
    'email' => env('SITE_EMAIL', 'Mutaz4st@gmail.com'),

    'handle' => env('SITE_HANDLE', 'mutaz_satti'),

    /*
    | حساب الدخول للوحة التحكم — منفصل عن بريد التواصل الظاهر في الموقع أعلاه.
    | يُستخدم في DatabaseSeeder عند تهيئة الحساب، والبريد يُحوّل إلى أحرف صغيرة
    | لأن Fortify يفعل الشيء نفسه عند الدخول.
    */
    'admin' => [
        'email' => env('ADMIN_EMAIL', 'mutaz4st@gmail.com'),
        'password' => env('ADMIN_PASSWORD', '0567043303'),
    ],

    /*
    | حسابات التواصل الاجتماعي — كلها على المعرّف نفسه.
    */
    'social' => [
        'instagram' => [
            'label' => 'إنستقرام',
            'url' => 'https://www.instagram.com/'.env('SITE_HANDLE', 'mutaz_satti'),
            'icon' => 'instagram',
        ],
        'youtube' => [
            'label' => 'يوتيوب',
            'url' => 'https://www.youtube.com/@'.env('SITE_HANDLE', 'mutaz_satti'),
            'icon' => 'youtube',
        ],
        'tiktok' => [
            'label' => 'تيك توك',
            'url' => 'https://www.tiktok.com/@'.env('SITE_HANDLE', 'mutaz_satti'),
            'icon' => 'tiktok',
        ],
        'snapchat' => [
            'label' => 'سناب شات',
            'url' => 'https://www.snapchat.com/add/'.env('SITE_HANDLE', 'mutaz_satti'),
            'icon' => 'snapchat',
        ],
    ],

    /*
    | الموقع الجغرافي — أهم إشارة لظهور الموقع في أسئلة "مصور في ...".
    */
    'location' => [
        'city' => env('SITE_CITY', 'جدة'),
        'city_en' => env('SITE_CITY_EN', 'Jeddah'),
        'region' => env('SITE_REGION', 'مكة المكرمة'),
        'country' => env('SITE_COUNTRY', 'SA'),
        'country_name' => 'المملكة العربية السعودية',
        'latitude' => (float) env('SITE_LATITUDE', 21.485811),
        'longitude' => (float) env('SITE_LONGITUDE', 39.192505),
    ],

    /*
    | نطاق الخدمة — المدن التي يغطيها التصوير، تُنشر ضمن areaServed.
    */
    'service_areas' => [
        'جدة',
        'مكة المكرمة',
        'الطائف',
        'رابغ',
        'ينبع',
        'الرياض',
        'المدينة المنورة',
    ],

    /*
    | ساعات العمل بصيغة Schema.org.
    */
    'opening_hours' => [
        ['days' => ['Saturday', 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday'], 'opens' => '09:00', 'closes' => '22:00'],
    ],

    /*
    | مقاسات الصور المولّدة عند الرفع — كلها بصيغة WebP.
    */
    /*
    |--------------------------------------------------------------------------
    | ألوان الأقسام
    |--------------------------------------------------------------------------
    |
    | لوحة محدودة بدل حقل لون حر: الألوان الحرة تُنتج تشكيلة غير متناسقة بسرعة،
    | وهذه مضبوطة لتبقى مقروءة على الخلفية الفاتحة والداكنة معًا. تُطبَّق عبر
    | متغيّرات CSS مضمّنة في السمة style، فلا تحتاج توليد أصناف Tailwind ديناميكية.
    |
    */

    'section_colors' => [
        'brand' => ['label' => 'ذهبي', 'light' => '#c06915', 'dark' => '#e6a333'],
        'teal' => ['label' => 'أزرق مخضّر', 'light' => '#0f766e', 'dark' => '#2dd4bf'],
        'sky' => ['label' => 'سماوي', 'light' => '#0369a1', 'dark' => '#38bdf8'],
        'violet' => ['label' => 'بنفسجي', 'light' => '#6d28d9', 'dark' => '#a78bfa'],
        'rose' => ['label' => 'وردي', 'light' => '#be123c', 'dark' => '#fb7185'],
        'emerald' => ['label' => 'أخضر', 'light' => '#047857', 'dark' => '#34d399'],
        'orange' => ['label' => 'برتقالي', 'light' => '#c2410c', 'dark' => '#fb923c'],
        'slate' => ['label' => 'رمادي', 'light' => '#475569', 'dark' => '#94a3b8'],
    ],

    /*
     * الاعتمادات والتراخيص الرسمية.
     *
     * مرجع واحد تقرؤه صفحة «نبذة» والبيانات المهيكلة معًا: الرقم المعروض للزائر
     * هو الرقم المنشور لأدوات البحث، فلا ينحرف أحدهما عن الآخر مع الوقت.
     *
     * وأسماء الجهات هنا كما تكتبها هي في شعاراتها لا كما تُختصر في الحديث:
     * «هيئة تقويم التعليم والتدريب» لا «التدريب والتعليم»، و«الهيئة العامة
     * لتنظيم الإعلام» لا «هيئة تنظيم الإعلام». الاسم الخطأ في صفحة اعتمادات
     * يُضعف الثقة التي وُضعت الصفحة لبنائها، وتقرؤه أدوات البحث كما هو.
     *
     * description جملة تامة قائمة بذاتها: فيها الاسم والصفة والجهة والرقم وما
     * تخوّله الرخصة. أدوات الذكاء الاصطناعي تقتبس الجملة لا الجدول، فالسطر
     * الذي يقول «رخصة رقم 1562» وحده لا يصلح جوابًا لسؤال، والجملة التامة
     * تصلح. و‎:owner‎ يُستبدل باسم المالك عند العرض، فلا يتكرّر الاسم في مصدرين.
     */
    'accreditations' => [
        [
            'key' => 'accr_etec',
            'title' => 'رخصة تدريب مهنية',
            'category' => 'رخصة تدريب',
            'authority' => 'هيئة تقويم التعليم والتدريب',
            'authority_en' => 'Education and Training Evaluation Commission',
            'authority_url' => 'https://etec.gov.sa',
            'label' => 'رخصة رقم',
            'number' => '1562',
            'description' => ':owner مدرّب مرخَّص من هيئة تقويم التعليم والتدريب برخصة تدريب مهنية رقم 1562، فورش التصوير الفوتوغرافي التي يقدّمها تُقام برخصة تدريب سارية لا بصفة شخصية.',
        ],
        [
            'key' => 'accr_gaca',
            'title' => 'رخصة مشغّل طائرة مسيّرة',
            'category' => 'رخصة تشغيل',
            'authority' => 'الهيئة العامة للطيران المدني',
            'authority_en' => 'General Authority of Civil Aviation',
            'authority_url' => 'https://gaca.gov.sa',
            'label' => 'رخصة رقم',
            'number' => 'RPC-O-18249',
            'description' => ':owner مشغّل طائرة مسيّرة مرخَّص من الهيئة العامة للطيران المدني برخصة رقم RPC-O-18249، فالتصوير الجوي للعقارات والمشاريع والفعاليات الذي يقدّمه يتم بتصريح تشغيل نظامي.',
        ],
        [
            'key' => 'accr_gamr',
            'title' => 'شهادة مهنية في الإعلام',
            'category' => 'شهادة مهنية',
            'authority' => 'الهيئة العامة لتنظيم الإعلام',
            'authority_en' => 'General Authority of Media Regulation',
            'authority_url' => 'https://gamr.gov.sa',
            'label' => 'تسجيل رقم',
            'number' => '679957',
            'description' => ':owner ممارس مهني مسجَّل لدى الهيئة العامة لتنظيم الإعلام برقم 679957، وهو التسجيل الذي يشمل إنتاج المحتوى المرئي والتغطيات الإعلامية.',
        ],
    ],

    'images' => [
        'quality' => 82,
        'variants' => [
            'thumb' => 400,
            'md' => 800,
            'lg' => 1600,
        ],
        'max_width' => 2400,
        'max_upload_kb' => 12288,
    ],

];
