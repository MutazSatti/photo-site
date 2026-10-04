<?php

use App\Models\Category;
use App\Models\Media;
use App\Models\PageBlock;
use App\Models\Section;
use Database\Seeders\PageBlockSeeder;
use Illuminate\Database\Migrations\Migration;

/**
 * محتوى صفحة تصوير الفعاليات والمؤتمرات.
 *
 * الصفحة كانت تُصيَّر بالقالب العام للأقسام الفرعية، وعنوانها منه: «الفعاليات
 * في جدة». وهو اسم قسمٍ لا اسم خدمة — لا يقول ماذا يُقدَّم ولا يطابق ما يكتبه
 * الباحث. فصارت لها صفحتها المصمَّمة على الرابط نفسه، عنوانها يسمّي الخدمة،
 * ونصّها كله يُحرَّر من «محتوى الصفحات».
 *
 * والنصوص البديلة لبعض الصور كانت تحمل الرابط اللاتيني للعمل، وبعضها يتكرّر
 * حرفيًا في كل صور المجموعة — فلا يصف شيئًا لقارئ الشاشة ولا لمحرّك البحث.
 */
return new class extends Migration
{
    public function up(): void
    {
        $section = Section::where('slug', Section::SERVICES)->first();

        $category = $section
            ? Category::where('section_id', $section->id)->where('slug', Category::ACTIVITIES)->first()
            : null;

        if (! $section || ! $category) {
            return;
        }

        /*
         * أوّل تشغيل يُعرف بغياب أقسام الصفحة. البذرة تستدعي هذه الهجرة بعد كل
         * نشر، فلا يصحّ أن تكتب فوق ما حرّره المالك مرّة ثانية.
         */
        $firstRun = ! PageBlock::onPage(PageBlock::ACTIVITIES)->exists();

        (new PageBlockSeeder)->seedPage(PageBlock::ACTIVITIES);

        if ($firstRun) {
            $this->describeCategory($category);
            $this->describeMedia();
        }
    }

    public function down(): void
    {
        PageBlock::onPage(PageBlock::ACTIVITIES)->delete();
        Media::where('usage', 'ac_hero')->update(['usage' => null]);
    }

    /**
     * وصف القسم كما يظهر في نتائج البحث.
     *
     * الوصف السابق صحيح لكنه يعدّد الأنواع ولا يقول شيئًا عمّا بعد التصوير.
     * وسؤال العميل بعد «هل تصوّر فعاليتي؟» هو «متى تصلني الصور؟».
     */
    private function describeCategory(Category $category): void
    {
        $city = config('site.location.city');

        $category->update([
            'seo_title' => "تصوير الفعاليات والمؤتمرات في {$city}",
            'seo_description' => "تصوير احترافي للفعاليات والمؤتمرات في {$city}: المعارض والملتقيات وافتتاحات الشركات، بتغطية كاملة ومعالجة احترافية وتسليم سريع للصور أثناء الفعالية.",
        ]);
    }

    /**
     * نصوص بديلة تصف ما في الصورة.
     *
     * المطابقة بالاسم الأصلي لا بالمعرّف: قاعدة الموقع المنشور تحمل الصور
     * نفسها بمعرّفات أخرى. وما لا يُطابَق يبقى على وصفه — لا يُفرَّغ.
     */
    private function describeMedia(): void
    {
        foreach ($this->descriptions() as $name => $alt) {
            Media::where('original_name', $name)->whereNotNull('post_id')->update(['alt' => $alt]);
        }
    }

    /**
     * @return array<string, string>
     */
    private function descriptions(): array
    {
        $city = config('site.location.city');
        $events = "— تصوير فعاليات في {$city}";
        $conf = "— تصوير مؤتمرات في {$city}";
        $training = "— توثيق برنامج تدريبي في {$city}";
        $opening = "— تصوير افتتاحات في {$city}";

        return [
            // مؤتمر ومعرض مصاحب
            'faaliyat-20240516-1.webp' => "الحضور في قاعة المؤتمر والمتحدّث على المنصّة {$conf}",
            'faaliyat-20240516-2.webp' => "تكريم المشاركين والتقاط الصور على هامش المؤتمر {$conf}",
            'faaliyat-20250120-1.webp' => "كلمة المتحدّث وركن الضيافة في الحفل المصاحب {$conf}",
            'faaliyat-20250120-2.webp' => "الفعالية المفتوحة المصاحبة للمؤتمر وزوّارها {$conf}",
            'faaliyat-20250120-3.webp' => "جلسة حوارية أمام لوحة الجهة المنظّمة {$conf}",
            'faaliyat-20250120-4.webp' => "لحظة الإعلان وسط القصاصات الذهبية {$conf}",

            // برنامج تدريبي
            'faaliyat-20240522-1.webp' => "مشاركون حول طاولات العمل في جلسة تدريبية {$training}",
            'faaliyat-20240626-1.webp' => "مدرّب يقدّم جلسة أمام المشاركين في قاعة التدريب {$training}",
            'faaliyat-20240626-2.webp' => "نشاط مائي ضمن الأنشطة المصاحبة للبرنامج {$events}",

            // افتتاح فرع تجاري
            'faaliyat-20241130-1.webp' => "فريق الفرع أمام الواجهة المزيّنة بالبالونات يوم الافتتاح {$opening}",
            'faaliyat-20241130-2.webp' => "قصّ شريط الافتتاح أمام الضيوف {$opening}",

            // بطولات وفعاليات مجتمعية
            'faaliyat-20240612-1.webp' => "منافسة بلياردو أمام الجمهور في بطولة داخلية {$events}",
            'faaliyat-20240628-1.webp' => "متطوّعو الإنقاذ وسبّاحون عند حوض السباحة في فعالية رياضية {$events}",
            'faaliyat-20240628-2.webp' => "حوض السباحة وركن المنظّمين في اليوم الرياضي {$events}",
            'faaliyat-20251128-1.webp' => "فريق المتطوّعين وصورة جماعية في فعالية مجتمعية {$events}",
            'faaliyat-20251128-2.webp' => "ركن تعريفي في الهواء الطلق ضمن حملة مجتمعية {$events}",
            'faaliyat-20251128-3.webp' => "ركن التسجيل واستقبال المشاركين في الفعالية {$events}",
            'faaliyat-20251129-1.webp' => "منظّمو الفعالية وصورة جماعية للفريق {$events}",
            'faaliyat-20251129-2.webp' => "تكريم المشاركين بالدروع في ختام الفعالية {$events}",
            'faaliyat-20251129-3.webp' => "صورة جماعية للحضور أمام خلفية الجهة المنظّمة {$events}",
            'faaliyat-20251129-4.webp' => "لقاءات الحضور وواجهة المقرّ مضاءة ليلًا {$events}",
            'faaliyat-20251129-5.webp' => "مصافحات ولقاءات جانبية بين الحضور {$events}",
            'faaliyat-20251129-6.webp' => "الحضور على المسرح في ختام الفعالية {$events}",
        ];
    }
};
