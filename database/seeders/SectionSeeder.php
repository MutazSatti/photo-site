<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Section;
use Illuminate\Database\Seeder;

class SectionSeeder extends Seeder
{
    public function run(): void
    {
        $owner = config('site.owner_name');
        $city = config('site.location.city');

        $sections = [
            [
                'slug' => Section::SERVICES,
                'name' => 'خدمات التصوير',
                'name_en' => 'Photography Services',
                'tagline' => 'تغطية احترافية للمناسبات والفعاليات والعقارات',
                'description' => "خدمات تصوير فوتوغرافي احترافية في {$city} تشمل تغطية المناسبات الخاصة والعامة، وتوثيق الفعاليات والمؤتمرات والمعارض والبرامج التدريبية، وتصوير العقارات للتسويق العقاري. تسليم الصور معالجة بالكامل وبجودة عالية.",
                'icon' => 'camera',
                'has_categories' => true,
                'sort_order' => 1,
                'seo_title' => "خدمات تصوير فوتوغرافي احترافي في {$city}",
                'seo_description' => "مصور فوتوغرافي محترف في {$city}. تغطية المناسبات، الفعاليات والمؤتمرات، وتصوير العقارات. حجز وتسليم سريع — {$owner}.",
            ],
            [
                'slug' => Section::WORKSHOPS,
                'name' => 'ورش تدريبية',
                'name_en' => 'Training Workshops',
                'tagline' => 'تعلّم التصوير من الأساس حتى الاحتراف',
                'description' => "ورش تدريبية عملية في التصوير الفوتوغرافي يقدّمها {$owner} في {$city}. تغطي الورش أساسيات الكاميرا والإضاءة والتكوين ومعالجة الصور، بأسلوب تطبيقي مباشر وتمارين ميدانية.",
                'icon' => 'academic',
                'sort_order' => 2,
                'seo_title' => "ورش تدريبية في التصوير الفوتوغرافي — {$city}",
                'seo_description' => "دورات وورش تدريبية عملية في التصوير الفوتوغرافي في {$city}: أساسيات الكاميرا، الإضاءة، التكوين، ومعالجة الصور. مقاعد محدودة.",
            ],
            [
                'slug' => Section::ARTICLES,
                'name' => 'مقالات',
                'name_en' => 'Articles',
                'tagline' => 'قراءات معمّقة في فن التصوير',
                'description' => 'مقالات متخصصة في التصوير الفوتوغرافي: تقنيات الإضاءة، اختيار العدسات، معالجة الصور، وبناء هوية بصرية للمصور. مكتوبة من خبرة ميدانية حقيقية.',
                'icon' => 'document',
                'sort_order' => 3,
                'seo_title' => 'مقالات في التصوير الفوتوغرافي',
                'seo_description' => 'مقالات متخصصة في التصوير الفوتوغرافي بالعربية: الإضاءة، العدسات، التكوين، المعالجة، ونصائح عملية من واقع التجربة الميدانية.',
            ],
            [
                'slug' => Section::TIPS,
                'name' => 'منشورات تعليمية',
                'name_en' => 'Educational Posts',
                'tagline' => 'معلومة قصيرة ومباشرة تحسّن صورتك اليوم',
                'description' => 'منشورات تعليمية مختصرة في التصوير الفوتوغرافي — كل منشور يعالج فكرة واحدة بشكل مباشر وقابل للتطبيق فورًا، مدعومة بأمثلة مصوّرة.',
                'icon' => 'lightbulb',
                'sort_order' => 4,
                'seo_title' => 'منشورات تعليمية في التصوير',
                'seo_description' => 'منشورات تعليمية قصيرة في التصوير الفوتوغرافي: نصائح سريعة، مقارنات إعدادات، وأمثلة عملية قابلة للتطبيق مباشرة.',
            ],
        ];

        foreach ($sections as $data) {
            Section::updateOrCreate(['slug' => $data['slug']], $data);
        }

        $this->seedServiceCategories();
    }

    /** الأقسام الفرعية الأربعة تحت "خدمات التصوير". */
    private function seedServiceCategories(): void
    {
        $section = Section::where('slug', Section::SERVICES)->firstOrFail();
        $city = config('site.location.city');
        $owner = config('site.owner_name');

        $categories = [
            [
                'slug' => Category::EVENTS,
                'name' => 'المناسبات',
                'name_en' => 'Events',
                'tagline' => 'توثيق يليق بلحظاتك',
                'description' => "تغطية تصويرية كاملة للمناسبات الخاصة والعامة في {$city}: حفلات التخرّج، الأعياد، المناسبات العائلية، وحفلات الشركات. تصوير يوثّق التفاصيل والانفعالات الحقيقية دون افتعال، مع تسليم ألبوم منسّق ومعالج.",
                'icon' => 'sparkles',
                'sort_order' => 1,
                'seo_title' => "تصوير المناسبات في {$city}",
                'seo_description' => "مصور مناسبات محترف في {$city}. تغطية حفلات التخرّج والمناسبات العائلية وفعاليات الشركات مع تسليم صور معالجة بجودة عالية — {$owner}.",
            ],
            [
                'slug' => Category::ACTIVITIES,
                'name' => 'الفعاليات',
                'name_en' => 'Events & Activities',
                'tagline' => 'تغطية إعلامية متكاملة',
                'description' => "تغطية تصويرية احترافية للفعاليات في {$city}: المؤتمرات والمعارض، الملتقيات والمنتديات، الفعاليات المؤسسية والمجتمعية، والبرامج التدريبية. توثيق للجلسات والمتحدثين وأجنحة العرض وتفاعل الحضور، مع تسليم سريع للصور المختارة أولًا بأول لاستخدامها في التغطية الإعلامية اللحظية.",
                'icon' => 'presentation',
                'sort_order' => 2,
                'seo_title' => "تصوير الفعاليات والمؤتمرات في {$city}",
                'seo_description' => "مصور فعاليات في {$city}. تغطية المؤتمرات والمعارض والملتقيات والفعاليات المؤسسية والبرامج التدريبية، مع تسليم فوري للصور المختارة.",
            ],
            [
                'slug' => Category::REAL_ESTATE,
                'name' => 'العقارات',
                'name_en' => 'Real Estate',
                'tagline' => 'صور تبيع العقار',
                'description' => "تصوير عقاري احترافي في {$city} للشقق والفلل والمكاتب والمعارض التجارية. استخدام عدسات واسعة وتصحيح المنظور والإضاءة المتوازنة لإظهار المساحات بأمانة وجاذبية، مع صور جاهزة للنشر على منصات التسويق العقاري.",
                'icon' => 'building',
                'sort_order' => 3,
                'seo_title' => "تصوير عقاري احترافي في {$city}",
                'seo_description' => "مصور عقارات في {$city}. تصوير الشقق والفلل والمكاتب بعدسات واسعة وإضاءة متوازنة، وصور جاهزة لمنصات التسويق العقاري.",
            ],
        ];

        foreach ($categories as $data) {
            Category::updateOrCreate(
                ['section_id' => $section->id, 'slug' => $data['slug']],
                $data + ['section_id' => $section->id],
            );
        }
    }
}
