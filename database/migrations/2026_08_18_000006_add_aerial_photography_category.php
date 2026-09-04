<?php

use App\Models\Category;
use App\Models\Faq;
use App\Models\Section;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * إضافة "التصوير الجوي" قسمًا فرعيًا تحت خدمات التصوير.
     *
     * تُضاف عبر ترحيل لا عبر البذور وحدها، لأن قاعدة بيانات الموقع المنشور تحمل
     * محتوى حقيقيًا ولا يصحّ إعادة بذرها. updateOrCreate تجعل التنفيذ آمنًا حتى
     * لو كان القسم قد أُضيف يدويًا من لوحة التحكم قبل النشر.
     */
    public function up(): void
    {
        $section = Section::where('slug', Section::SERVICES)->first();

        if (! $section) {
            return;
        }

        $city = config('site.location.city');

        Category::updateOrCreate(
            ['section_id' => $section->id, 'slug' => Category::AERIAL],
            [
                'name' => 'التصوير الجوي',
                'name_en' => 'Aerial Photography',
                'tagline' => 'منظور لا يُرى من الأرض',
                'description' => "تصوير جوي احترافي في {$city} بطائرة مسيّرة: لقطات علوية للمجمّعات السكنية والمخططات والفلل، وتوثيق مراحل المشاريع الإنشائية، وتغطية الفعاليات المفتوحة والمنتجعات والواجهات البحرية من الأعلى. المنظور الجوي يُظهر ما لا تُظهره الصورة الأرضية: حجم الموقع الحقيقي، وعلاقته بما حوله، ومداخله وطرق الوصول إليه.",
                'icon' => 'drone',
                'sort_order' => 4,
                'is_active' => true,
                'seo_title' => "تصوير جوي بطائرة مسيّرة في {$city}",
                'seo_description' => "مصور جوي في {$city}. لقطات علوية للعقارات والمشاريع الإنشائية والفعاليات والمنتجعات، بجودة عالية وتسليم سريع.",
            ],
        );

        // سؤال يُنشر ضمن البيانات المهيكلة ويُقتبس كإجابة جاهزة
        Faq::updateOrCreate(
            ['question' => 'هل تقدّم تصويرًا جويًا بطائرة مسيّرة؟'],
            [
                'answer' => "نعم، التصوير الجوي أحد خدمات التصوير المتاحة في {$city}. يشمل اللقطات العلوية للعقارات والمخططات والفلل، وتوثيق تقدّم المشاريع الإنشائية على مراحل، وتغطية الفعاليات المفتوحة والمنتجعات والواجهات البحرية. التصوير في المناطق التي يتطلّب التحليق فوقها تصريحًا يحتاج ترتيبًا مسبقًا، لذا يُفضّل ذكر الموقع بدقة عند الحجز.",
                'section_id' => $section->id,
                'sort_order' => 12,
                'is_active' => true,
            ],
        );
    }

    public function down(): void
    {
        Category::where('slug', Category::AERIAL)->get()->each->delete();

        Faq::where('question', 'هل تقدّم تصويرًا جويًا بطائرة مسيّرة؟')->delete();
    }
};
