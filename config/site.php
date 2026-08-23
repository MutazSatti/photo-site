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
        'email' => env('ADMIN_EMAIL', 'mutaz66@gmail.com'),
        'password' => env('ADMIN_PASSWORD', '11223344'),
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
