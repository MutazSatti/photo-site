<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $owner = config('site.owner_name');
        $city = config('site.location.city');

        $settings = [
            // ---------- الصفحة الرئيسية ----------
            ['key' => 'hero_title', 'group' => 'home', 'type' => 'text', 'label' => 'عنوان الواجهة', 'sort_order' => 1,
                'value' => "{$owner} — مصور فوتوغرافي محترف في {$city}"],
            ['key' => 'hero_subtitle', 'group' => 'home', 'type' => 'textarea', 'label' => 'نص الواجهة', 'sort_order' => 2,
                'value' => 'أوثّق المناسبات والفعاليات والمعارض والعقارات بصورة تحكي القصة كما حدثت. خبرة ميدانية، تسليم سريع، وجودة لا تُساوم.'],
            ['key' => 'hero_cta', 'group' => 'home', 'type' => 'text', 'label' => 'نص زر الحجز', 'sort_order' => 3,
                'value' => 'احجز موعد تصوير'],

            // ---------- نبذة ----------
            ['key' => 'about_title', 'group' => 'general', 'type' => 'text', 'label' => 'عنوان النبذة', 'sort_order' => 1,
                'value' => 'نبذة عني'],
            ['key' => 'about_body', 'group' => 'general', 'type' => 'textarea', 'label' => 'نص النبذة', 'sort_order' => 2,
                'value' => "أنا {$owner}، مصور فوتوغرافي محترف مقيم في {$city}. بدأت رحلتي مع الكاميرا شغفًا، وتحوّلت مع السنوات إلى عمل يومي أوثّق فيه المناسبات والمؤتمرات والمعارض والدورات التدريبية والعقارات.\n\nأؤمن أن الصورة الجيدة ليست الأكثر فلترة، بل الأصدق في نقل اللحظة. لذلك أعمل على الضوء الطبيعي قدر الإمكان، وأتجنّب المبالغة في المعالجة، وأركّز على التفاصيل التي تصنع الفرق: نظرة، حركة يد، أو زاوية تُظهر المكان على حقيقته.\n\nإضافة إلى العمل الميداني، أقدّم ورشًا تدريبية لمن يريد تعلّم التصوير من الأساس، وأنشر مقالات ومنشورات تعليمية تختصر على المصور المبتدئ سنوات من التجربة."],
            ['key' => 'about_years', 'group' => 'general', 'type' => 'number', 'label' => 'سنوات الخبرة', 'sort_order' => 3, 'value' => '10'],
            ['key' => 'stat_projects', 'group' => 'general', 'type' => 'number', 'label' => 'عدد المشاريع', 'sort_order' => 4, 'value' => '450'],
            ['key' => 'stat_clients', 'group' => 'general', 'type' => 'number', 'label' => 'عدد العملاء', 'sort_order' => 5, 'value' => '180'],
            ['key' => 'stat_workshops', 'group' => 'general', 'type' => 'number', 'label' => 'عدد الورش', 'sort_order' => 6, 'value' => '35'],

            // ---------- الشعار ----------
            // مجموعة منفصلة لا تظهر في تبويبات الإعدادات، لأن هذه الخيارات
            // تُصيَّر يدويًا داخل بطاقة الشعار بجوار المعاينة.
            ['key' => 'logo_max_height', 'group' => 'logo', 'type' => 'number', 'label' => 'أقصى ارتفاع للشعار (بكسل)', 'sort_order' => 1, 'value' => '40'],
            ['key' => 'logo_adapt_dark', 'group' => 'logo', 'type' => 'boolean', 'label' => 'تكييف الشعار مع الوضع الداكن', 'sort_order' => 2, 'value' => '0'],
            ['key' => 'logo_base_color', 'group' => 'logo', 'type' => 'select', 'label' => 'لون الشعار الأصلي', 'sort_order' => 3, 'value' => 'black'],

            // ---------- النص بجانب الشعار ----------
            ['key' => 'brand_name', 'group' => 'logo', 'type' => 'text', 'label' => 'الاسم بجانب الشعار', 'sort_order' => 4, 'value' => $owner],
            ['key' => 'brand_tagline', 'group' => 'logo', 'type' => 'text', 'label' => 'الوصف تحت الاسم', 'sort_order' => 5,
                'value' => "مصور فوتوغرافي — {$city}"],
            ['key' => 'brand_text_header', 'group' => 'logo', 'type' => 'select', 'label' => 'النص في الترويسة', 'sort_order' => 6, 'value' => 'both'],
            ['key' => 'brand_text_footer', 'group' => 'logo', 'type' => 'select', 'label' => 'النص في التذييل', 'sort_order' => 7, 'value' => 'name'],

            // ---------- اللون الرئيسي ----------
            // فارغ = التدرّج الذهبي الأصلي المصمَّم يدويًا في app.css.
            // أي قيمة hex هنا تُولّد تدرّجًا كاملًا يتجاوزه.
            ['key' => 'brand_color', 'group' => 'logo', 'type' => 'color', 'label' => 'اللون الرئيسي', 'sort_order' => 8, 'value' => ''],

            // ---------- التواصل ----------
            ['key' => 'contact_phone', 'group' => 'contact', 'type' => 'text', 'label' => 'رقم الجوال', 'sort_order' => 1,
                'value' => config('site.phone_local')],
            ['key' => 'contact_whatsapp', 'group' => 'contact', 'type' => 'text', 'label' => 'رقم الواتساب (دولي بدون +)', 'sort_order' => 2,
                'value' => config('site.whatsapp')],
            ['key' => 'contact_email', 'group' => 'contact', 'type' => 'text', 'label' => 'البريد الإلكتروني', 'sort_order' => 3,
                'value' => config('site.email')],
            ['key' => 'contact_city', 'group' => 'contact', 'type' => 'text', 'label' => 'المدينة', 'sort_order' => 4, 'value' => $city],
            ['key' => 'contact_note', 'group' => 'contact', 'type' => 'textarea', 'label' => 'ملاحظة صفحة التواصل', 'sort_order' => 5,
                'value' => 'الواتساب هو أسرع وسيلة للوصول إليّ. أرجو ذكر نوع المناسبة وتاريخها ومكانها في أول رسالة ليصلك عرض دقيق مباشرة.'],

            // ---------- التواصل الاجتماعي ----------
            ['key' => 'social_instagram', 'group' => 'social', 'type' => 'url', 'label' => 'إنستقرام', 'sort_order' => 1,
                'value' => config('site.social.instagram.url')],
            ['key' => 'social_youtube', 'group' => 'social', 'type' => 'url', 'label' => 'يوتيوب', 'sort_order' => 2,
                'value' => config('site.social.youtube.url')],
            ['key' => 'social_tiktok', 'group' => 'social', 'type' => 'url', 'label' => 'تيك توك', 'sort_order' => 3,
                'value' => config('site.social.tiktok.url')],
            ['key' => 'social_snapchat', 'group' => 'social', 'type' => 'url', 'label' => 'سناب شات', 'sort_order' => 4,
                'value' => config('site.social.snapchat.url')],

            // ---------- السيو ----------
            ['key' => 'seo_title', 'group' => 'seo', 'type' => 'text', 'label' => 'عنوان الموقع', 'sort_order' => 1,
                'value' => "{$owner} | مصور فوتوغرافي محترف في {$city}"],
            ['key' => 'seo_description', 'group' => 'seo', 'type' => 'textarea', 'label' => 'وصف الموقع', 'sort_order' => 2,
                'value' => "{$owner} مصور فوتوغرافي محترف في {$city}، متخصص في تصوير المناسبات والفعاليات والمؤتمرات والمعارض والعقارات، ويقدّم ورشًا تدريبية في التصوير. للحجز: ".config('site.phone_local')],
            ['key' => 'seo_keywords', 'group' => 'seo', 'type' => 'textarea', 'label' => 'الكلمات المفتاحية', 'sort_order' => 3,
                'value' => "مصور {$city}، مصور فوتوغرافي، تصوير مناسبات، تصوير فعاليات، مصور فعاليات، تصوير مؤتمرات، تصوير معارض، تصوير عقارات، ورش تصوير، مصور محترف السعودية، {$owner}"],
        ];

        foreach ($settings as $data) {
            Setting::updateOrCreate(['key' => $data['key']], $data);
        }

        Setting::flush();
    }
}
